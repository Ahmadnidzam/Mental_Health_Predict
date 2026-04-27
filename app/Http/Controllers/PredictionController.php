<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class PredictionController extends Controller
{
    /**
     * Daftar fitur yang akan dikirim ke Python script.
     */
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

    /**
     * Tampilkan form prediksi.
     */
    public function showForm()
    {
        return view('predict');
    }

    /**
     * Proses form prediksi: panggil Python predict.py, simpan ke DB,
     * lalu kembalikan response JSON (untuk AJAX) atau redirect (non-AJAX).
     */
    public function predict(Request $request)
    {
        $rules = [
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

        // Hanya ambil 24 fitur (buang token CSRF dll.)
        $features = collect($validated)
            ->only(self::FEATURE_FIELDS)
            ->toArray();

        $output = $this->callPythonPredict($features);

        if (! is_array($output) || ($output['status'] ?? null) !== 'success') {
            $message = $output['message'] ?? 'Gagal menjalankan prediksi (Python script error).';
            Log::error('predict.py failed', ['payload' => $features, 'output' => $output]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $message,
                ], 500);
            }

            return back()->withInput()->with('error', $message);
        }

        $prediction = Prediction::create([
            'input_features'   => $features,
            'knn_prediction'   => $output['knn'],
            'svm_prediction'   => $output['svm'],
            'dt_prediction'    => $output['dt'],
            'knn_confidence'   => $output['knn_confidence'],
            'svm_confidence'   => $output['svm_confidence'],
            'dt_confidence'    => $output['dt_confidence'],
            'final_prediction' => $output['final'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status'     => 'success',
                'prediction' => $prediction,
                'labels'     => $output['labels'] ?? null,
            ]);
        }

        return redirect()
            ->route('history')
            ->with('success', 'Prediksi berhasil disimpan.');
    }

    /**
     * Detail satu prediksi (untuk JSON / modal di halaman history).
     */
    public function show(Prediction $prediction): JsonResponse
    {
        return response()->json([
            'id'             => $prediction->id,
            'created_at'     => $prediction->created_at->format('d/m/Y H:i'),
            'input_features' => $prediction->input_features,
            'knn'            => ['pred' => $prediction->knn_prediction, 'conf' => $prediction->knn_confidence, 'label' => $prediction->knn_label],
            'svm'            => ['pred' => $prediction->svm_prediction, 'conf' => $prediction->svm_confidence, 'label' => $prediction->svm_label],
            'dt'             => ['pred' => $prediction->dt_prediction, 'conf' => $prediction->dt_confidence, 'label' => $prediction->dt_label],
            'final'          => ['pred' => $prediction->final_prediction, 'label' => $prediction->final_label],
        ]);
    }

    /**
     * Panggil Python predict.py via Symfony Process.
     *
     * @param  array<string, mixed>  $features
     * @return array<string, mixed>|null
     */
    private function callPythonPredict(array $features): ?array
    {
        $python = env('PYTHON_PATH', 'python');
        $script = storage_path('models/predict.py');

        if (! file_exists($script)) {
            return ['status' => 'error', 'message' => "predict.py tidak ditemukan di {$script}"];
        }

        $jsonArg = json_encode($features);

        // Di Windows, env yang dibawa PHP (terutama dari XAMPP) sering
        // tidak memuat C:\Windows\System32 di PATH. Akibatnya Python tidak
        // bisa load DLL Winsock saat `import _overlapped` -> WinError 10106.
        // Kita override env untuk menjamin PATH lengkap dan SystemRoot ter-set.
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

        // Hanya log detail call kalau APP_DEBUG=true.
        if (config('app.debug')) {
            Log::debug('[PREDICT] Python call', [
                'python_path' => $python,
                'cmdline'     => $process->getCommandLine(),
                'exit_code'   => $process->getExitCode(),
                'stderr'      => $stderr,
            ]);
        }

        if (! $process->isSuccessful()) {
            // Python mungkin meng-print JSON error walau exit-code != 0
            $decoded = json_decode($stdout, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            return [
                'status'  => 'error',
                'message' => $stderr !== '' ? $stderr : 'Process gagal (exit code ' . $process->getExitCode() . ').',
            ];
        }

        $decoded = json_decode($stdout, true);
        if (! is_array($decoded)) {
            return [
                'status'  => 'error',
                'message' => 'Output Python tidak valid JSON: ' . substr($stdout, 0, 200),
            ];
        }

        return $decoded;
    }
}
