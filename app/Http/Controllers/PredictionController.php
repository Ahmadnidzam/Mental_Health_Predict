<?php

namespace App\Http\Controllers;

use App\Jobs\RetrainModelsJob;
use App\Models\Prediction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PredictionController extends Controller
{
    private const FEATURE_FIELDS = [
        'age', 'gender', 'marital_status', 'education_level', 'employment_status',
        'sleep_hours', 'physical_activity_hours_per_week', 'screen_time_hours_per_day',
        'social_support_score', 'work_stress_level', 'academic_pressure_level',
        'job_satisfaction_score', 'financial_stress_level', 'working_hours_per_week',
        'anxiety_score', 'depression_score', 'stress_level', 'mood_swings_frequency',
        'concentration_difficulty_level', 'panic_attack_history',
        'family_history_mental_illness', 'previous_mental_health_diagnosis',
        'therapy_history', 'substance_use',
    ];

    private const ALL_MODELS = ['knn', 'knn_hpo', 'svm', 'svm_hpo', 'dt', 'dt_hpo'];

    public function showForm()
    {
        return view('predict');
    }

    public function predict(Request $request)
    {
        $rules = array_merge(
            ['model' => 'required|string|in:' . implode(',', self::ALL_MODELS)],
            $this->featureRules()
        );

        $validated = $request->validate($rules);

        $selectedModel = $validated['model'];
        $features = collect($validated)->only(self::FEATURE_FIELDS)->toArray();

        $payload = array_merge(['model' => $selectedModel], $features);
        $output  = $this->callPythonPredict($payload);

        if (! is_array($output) || ($output['status'] ?? null) !== 'success') {
            $message = $output['message'] ?? 'Gagal menjalankan prediksi (Python script error).';
            Log::error('predict.py failed', ['payload' => $payload, 'output' => $output]);

            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 500);
            }

            return back()->withInput()->with('error', $message);
        }

        $countBefore = Prediction::count();

        $prediction = Prediction::create([
            'user_id'          => auth()->id(),
            'selected_model'   => $output['model'],
            'input_features'   => $features,
            'final_prediction' => $output['prediction'],
            'confidence'       => $output['confidence'],
        ]);

        $this->maybeDispatchRetrain($countBefore);

        if ($request->wantsJson()) {
            return response()->json([
                'status'     => 'success',
                'prediction' => [
                    'id'             => $prediction->id,
                    'selected_model' => $prediction->selected_model,
                    'model_label'    => $prediction->model_label,
                    'final_prediction' => $prediction->final_prediction,
                    'confidence'     => $prediction->confidence,
                    'label'          => $prediction->final_label,
                ],
            ]);
        }

        return redirect()->route('history')->with('success', 'Prediksi berhasil disimpan.');
    }

    public function predictCsv(Request $request): JsonResponse
    {
        $request->validate([
            'model' => 'required|string|in:' . implode(',', self::ALL_MODELS),
            'csv'   => 'required|file|max:10240|mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,application/octet-stream',
        ]);

        $selectedModel = $request->input('model');
        $path = $request->file('csv')->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return response()->json(['status' => 'error', 'message' => 'Gagal membaca file CSV.'], 422);
        }

        $firstRow = fgetcsv($handle);
        if ($firstRow === false) {
            fclose($handle);
            return response()->json(['status' => 'error', 'message' => 'File CSV kosong.'], 422);
        }

        $firstRow = array_map('trim', $firstRow);

        // Deteksi otomatis: header vs data
        // Jika baris pertama mengandung minimal 1 nama kolom yang dikenal → ada header
        $knownCols  = array_intersect($firstRow, self::FEATURE_FIELDS);
        $hasHeader  = count($knownCols) > 0;

        if ($hasHeader) {
            // Validasi header lengkap
            $missingCols = array_diff(self::FEATURE_FIELDS, $firstRow);
            if (!empty($missingCols)) {
                fclose($handle);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Header CSV tidak lengkap. Kolom kurang: ' . implode(', ', $missingCols),
                ], 422);
            }
            $header = $firstRow;
            $startRowNum = 2; // baris data mulai dari baris 2
        } else {
            // Tidak ada header — asumsikan urutan kolom = FEATURE_FIELDS (24 kolom)
            // Baris pertama adalah data, kolom ke-25 (mental_health_risk) di-ignore
            if (count($firstRow) < count(self::FEATURE_FIELDS)) {
                fclose($handle);
                return response()->json([
                    'status'  => 'error',
                    'message' => 'CSV tanpa header harus memiliki minimal ' . count(self::FEATURE_FIELDS) . ' kolom (urutan sesuai template).',
                ], 422);
            }
            $header = self::FEATURE_FIELDS; // mapping tetap ke urutan baku
            $startRowNum = 1;               // baris pertama adalah data baris 1
        }

        // Kumpulkan semua baris data (parse dulu, predict sekali batch)
        $pendingRows = $hasHeader ? [] : [$firstRow];
        while (($row = fgetcsv($handle)) !== false) {
            $pendingRows[] = array_map('trim', $row);
        }
        fclose($handle);

        // Parse + validasi tiap baris. Baris invalid langsung jadi entri error,
        // hanya baris valid yang dikirim ke Python.
        $featuresList = [];   // [['age'=>..., 'gender'=>..., ...], ...]
        $rowNumbers   = [];   // nomor baris sumber per index
        $results      = [];   // hasil akhir (error + sukses), diurut nanti
        $rules        = $this->featureRules();
        $rowNum       = $startRowNum - 1;

        foreach ($pendingRows as $row) {
            $rowNum++;
            if (count($row) < count(self::FEATURE_FIELDS)) {
                $results[] = ['row' => $rowNum, 'status' => 'error', 'message' => 'Jumlah kolom kurang dari ' . count(self::FEATURE_FIELDS) . '.'];
                continue;
            }

            if ($hasHeader) {
                $data = array_combine($header, array_slice($row, 0, count($header)));
            } else {
                $data = array_combine(self::FEATURE_FIELDS, array_slice($row, 0, count(self::FEATURE_FIELDS)));
            }

            $features = [];
            foreach (self::FEATURE_FIELDS as $field) {
                $features[$field] = trim($data[$field] ?? '');
            }

            $validator = Validator::make($features, $rules);
            if ($validator->fails()) {
                $results[] = [
                    'row'     => $rowNum,
                    'status'  => 'error',
                    'message' => implode(' ', $validator->errors()->all()),
                    'input'   => $features,
                ];
                continue;
            }

            $featuresList[] = $features;
            $rowNumbers[]   = $rowNum;
        }

        if (empty($featuresList)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak ada baris data yang valid di CSV.',
                'results' => $results,
            ], 422);
        }

        // Prediksi batch — chunk agar memori & ukuran payload tetap terjaga.
        $chunkSize = 500;
        $pyResults = [];
        foreach (array_chunk($featuresList, $chunkSize) as $chunk) {
            $batchOutput = $this->callPythonPredict(['model' => $selectedModel, 'rows' => $chunk]);

            if (! is_array($batchOutput) || ($batchOutput['status'] ?? null) !== 'success') {
                return response()->json([
                    'status'  => 'error',
                    'message' => $batchOutput['message'] ?? 'Python batch predict gagal.',
                ], 500);
            }

            foreach ($batchOutput['results'] ?? [] as $r) {
                $pyResults[] = $r;
            }
        }

        $countBefore = Prediction::count();
        $userId      = auth()->id();

        // Simpan semua hasil dalam satu transaction → atomik.
        DB::transaction(function () use ($featuresList, $rowNumbers, $pyResults, $selectedModel, $userId, &$results) {
            foreach ($featuresList as $i => $features) {
                $srcRow = $rowNumbers[$i];
                $py     = $pyResults[$i] ?? null;

                if (! $py) {
                    $results[] = ['row' => $srcRow, 'status' => 'error', 'message' => 'Tidak ada hasil dari Python.', 'input' => $features];
                    continue;
                }

                $prediction = Prediction::create([
                    'user_id'          => $userId,
                    'selected_model'   => $selectedModel,
                    'input_features'   => $features,
                    'final_prediction' => $py['prediction'],
                    'confidence'       => $py['confidence'],
                ]);

                $results[] = [
                    'row'              => $srcRow,
                    'status'           => 'success',
                    'id'               => $prediction->id,
                    'final_prediction' => $py['prediction'],
                    'label'            => $py['label'],
                    'confidence'       => $py['confidence'],
                    'input'            => $features,
                ];
            }
        });

        // Urutkan hasil sesuai nomor baris sumber.
        usort($results, fn ($a, $b) => $a['row'] <=> $b['row']);

        $this->maybeDispatchRetrain($countBefore);

        return response()->json([
            'status'  => 'success',
            'model'   => $selectedModel,
            'total'   => count($results),
            'results' => $results,
        ]);
    }

    /**
     * Aturan validasi 24 fitur — dipakai jalur manual & jalur CSV.
     */
    private function featureRules(): array
    {
        return [
            'age'                              => 'required|numeric|min:1|max:120',
            'gender'                           => 'required|string|in:Male,Female,Other',
            'marital_status'                   => 'required|string|in:Single,Married,Divorced',
            'education_level'                  => 'required|string|in:High School,Bachelor,Master,PhD',
            'employment_status'                => 'required|string|in:Employed,Unemployed,Self-Employed,Student',
            'sleep_hours'                      => 'required|numeric|min:0|max:24',
            'physical_activity_hours_per_week' => 'required|numeric|min:0|max:168',
            'screen_time_hours_per_day'        => 'required|numeric|min:0|max:24',
            'social_support_score'             => 'required|integer|min:0|max:10',
            'work_stress_level'                => 'required|integer|min:0|max:10',
            'academic_pressure_level'          => 'required|integer|min:0|max:10',
            'job_satisfaction_score'           => 'required|integer|min:0|max:10',
            'financial_stress_level'           => 'required|integer|min:0|max:10',
            'working_hours_per_week'           => 'required|integer|min:0|max:168',
            'anxiety_score'                    => 'required|integer|min:0|max:10',
            'depression_score'                 => 'required|integer|min:0|max:10',
            'stress_level'                     => 'required|integer|min:0|max:10',
            'mood_swings_frequency'            => 'required|integer|min:0|max:10',
            'concentration_difficulty_level'   => 'required|integer|min:0|max:10',
            'panic_attack_history'             => 'required|in:0,1',
            'family_history_mental_illness'    => 'required|in:0,1',
            'previous_mental_health_diagnosis' => 'required|in:0,1',
            'therapy_history'                  => 'required|in:0,1',
            'substance_use'                    => 'required|in:0,1',
        ];
    }

    public function show(Prediction $prediction): JsonResponse
    {
        if ($prediction->user_id !== auth()->id()) {
            abort(403);
        }

        return response()->json([
            'id'             => $prediction->id,
            'created_at'     => $prediction->created_at->format('d/m/Y H:i'),
            'selected_model' => $prediction->selected_model,
            'model_label'    => $prediction->model_label,
            'input_features' => $prediction->input_features,
            'final_prediction' => $prediction->final_prediction,
            'confidence'     => $prediction->confidence,
            'label'          => $prediction->final_label,
        ]);
    }

    private function maybeDispatchRetrain(int $countBefore = -1): void
    {
        $retainEvery = (int) config('services.retrain.every', 50);
        $total       = Prediction::count();

        $shouldRetrain = $countBefore < 0
            ? ($total > 0 && $total % $retainEvery === 0)
            : (floor($total / $retainEvery) > floor($countBefore / $retainEvery));

        if ($shouldRetrain) {
            RetrainModelsJob::dispatch();
            Log::info("[Retrain] Job dispatched — total prediksi: {$total}");
        }
    }

    private function callPythonPredict(array $payload): ?array
    {
        $python  = config('services.python.path', 'python');
        $script  = storage_path('models/predict.py');

        if (! file_exists($script)) {
            return ['status' => 'error', 'message' => "predict.py tidak ditemukan di {$script}"];
        }

        // Tulis JSON ke file temporer agar tidak kena batas panjang argv
        $tmpIn = tempnam(sys_get_temp_dir(), 'mhp_in_');
        file_put_contents($tmpIn, json_encode($payload));

        // proc_open dengan env=null → OS-level inheritance, TIDAK menserialisasi
        // env block → menghindari batas 32767 karakter Windows dari Symfony Process
        $descriptors = [
            0 => ['file', $tmpIn, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open([$python, $script, '--stdin'], $descriptors, $pipes, null, null);

        if (! is_resource($proc)) {
            @unlink($tmpIn);
            return ['status' => 'error', 'message' => 'Gagal menjalankan Python process.'];
        }

        $stdout   = trim(stream_get_contents($pipes[1]));
        $stderr   = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        // Hapus tempfile setelah proc selesai — di Windows file tidak bisa
        // di-unlink selama child masih memegangnya sebagai stdin.
        @unlink($tmpIn);

        if (config('app.debug')) {
            Log::debug('[PREDICT] Python call', [
                'python_path' => $python,
                'exit_code'   => $exitCode,
                'stderr'      => $stderr,
            ]);
        }

        $decoded = json_decode($stdout, true);
        if (! is_array($decoded)) {
            return [
                'status'  => 'error',
                'message' => $stderr !== '' ? $stderr : 'Output Python tidak valid JSON: ' . substr($stdout, 0, 200),
            ];
        }

        return $decoded;
    }
}
