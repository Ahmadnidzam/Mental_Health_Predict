<?php

namespace App\Http\Controllers;

use App\Jobs\RetrainModelsJob;
use App\Models\Prediction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

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

    private const AVAILABLE_MODELS = ['svm', 'svm_hpo'];

    private const ALL_MODELS = ['knn', 'knn_hpo', 'svm', 'svm_hpo', 'dt', 'dt_hpo'];

    public function showForm()
    {
        return view('predict');
    }

    public function predict(Request $request)
    {
        $rules = [
            'model'                            => 'required|string|in:' . implode(',', self::ALL_MODELS),
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

        $prediction = Prediction::create([
            'user_id'          => auth()->id(),
            'selected_model'   => $output['model'],
            'input_features'   => $features,
            'final_prediction' => $output['prediction'],
            'confidence'       => $output['confidence'],
        ]);

        $this->maybeDispatchRetrain();

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
            'csv'   => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $selectedModel = $request->input('model');
        $path = $request->file('csv')->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return response()->json(['status' => 'error', 'message' => 'Gagal membaca file CSV.'], 422);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return response()->json(['status' => 'error', 'message' => 'File CSV kosong.'], 422);
        }

        $header = array_map('trim', $header);
        $missingCols = array_diff(self::FEATURE_FIELDS, $header);
        if (!empty($missingCols)) {
            fclose($handle);
            return response()->json([
                'status'  => 'error',
                'message' => 'Kolom tidak lengkap: ' . implode(', ', $missingCols),
            ], 422);
        }

        $results     = [];
        $rowNum      = 1;
        $countBefore = Prediction::count();

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($row) !== count($header)) continue;

            $data = array_combine($header, $row);
            $features = [];
            foreach (self::FEATURE_FIELDS as $field) {
                $features[$field] = trim($data[$field] ?? '');
            }

            $payload = array_merge(['model' => $selectedModel], $features);
            $output  = $this->callPythonPredict($payload);

            if (!is_array($output) || ($output['status'] ?? null) !== 'success') {
                $results[] = [
                    'row'    => $rowNum,
                    'status' => 'error',
                    'message' => $output['message'] ?? 'Gagal prediksi',
                    'input'  => $features,
                ];
                continue;
            }

            $prediction = Prediction::create([
                'user_id'          => auth()->id(),
                'selected_model'   => $output['model'],
                'input_features'   => $features,
                'final_prediction' => $output['prediction'],
                'confidence'       => $output['confidence'],
            ]);

            $results[] = [
                'row'              => $rowNum,
                'status'           => 'success',
                'id'               => $prediction->id,
                'final_prediction' => $prediction->final_prediction,
                'label'            => $prediction->final_label,
                'confidence'       => $prediction->confidence,
                'input'            => $features,
            ];
        }

        fclose($handle);

        $this->maybeDispatchRetrain($countBefore ?? 0);

        return response()->json([
            'status'  => 'success',
            'model'   => $selectedModel,
            'total'   => count($results),
            'results' => $results,
        ]);
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
        $retainEvery = (int) env('RETRAIN_EVERY', 50);
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
        $python = env('PYTHON_PATH', 'python');
        $script = storage_path('models/predict.py');

        if (! file_exists($script)) {
            return ['status' => 'error', 'message' => "predict.py tidak ditemukan di {$script}"];
        }

        $jsonArg = json_encode($payload);

        $env = null;
        if (PHP_OS_FAMILY === 'Windows') {
            $systemRoot = getenv('SystemRoot') ?: 'C:\\Windows';
            $sys32      = $systemRoot . '\\System32';
            $extraPath  = $sys32 . ';' . $systemRoot . ';' . $sys32 . '\\Wbem;' . $sys32 . '\\WindowsPowerShell\\v1.0';
            $currentPath = getenv('PATH') ?: '';
            $env = [
                'PATH'         => $extraPath . ';' . $currentPath,
                'SystemRoot'   => $systemRoot,
                'SYSTEMROOT'   => $systemRoot,
                'WINDIR'       => $systemRoot,
                'SYSTEMDRIVE'  => getenv('SYSTEMDRIVE')  ?: 'C:',
                'TEMP'         => getenv('TEMP')         ?: $systemRoot . '\\Temp',
                'TMP'          => getenv('TMP')          ?: $systemRoot . '\\Temp',
                'USERPROFILE'  => getenv('USERPROFILE')  ?: '',
                'LOCALAPPDATA' => getenv('LOCALAPPDATA') ?: '',
                'APPDATA'      => getenv('APPDATA')      ?: '',
                'COMPUTERNAME' => getenv('COMPUTERNAME') ?: '',
                'COMSPEC'      => getenv('COMSPEC')      ?: $sys32 . '\\cmd.exe',
                'PATHEXT'      => getenv('PATHEXT')      ?: '.COM;.EXE;.BAT;.CMD',
                'NUMBER_OF_PROCESSORS' => getenv('NUMBER_OF_PROCESSORS') ?: '4',
            ];
        }

        $process = new Process([$python, $script, $jsonArg], null, $env);
        $process->setTimeout(60);
        $process->run();

        $stdout = trim($process->getOutput());
        $stderr = trim($process->getErrorOutput());

        if (config('app.debug')) {
            Log::debug('[PREDICT] Python call', [
                'python_path' => $python,
                'cmdline'     => $process->getCommandLine(),
                'exit_code'   => $process->getExitCode(),
                'stderr'      => $stderr,
            ]);
        }

        if (! $process->isSuccessful()) {
            $decoded = json_decode($stdout, true);
            if (is_array($decoded)) return $decoded;
            return [
                'status'  => 'error',
                'message' => $stderr !== '' ? $stderr : 'Process gagal (exit code ' . $process->getExitCode() . ').',
            ];
        }

        $decoded = json_decode($stdout, true);
        if (! is_array($decoded)) {
            return ['status' => 'error', 'message' => 'Output Python tidak valid JSON: ' . substr($stdout, 0, 200)];
        }

        return $decoded;
    }
}
