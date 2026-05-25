<?php

namespace App\Jobs;

use App\Models\ModelMetric;
use App\Models\Prediction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class RetrainModelsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600; // 10 menit
    public int $tries   = 1;   // tidak retry jika gagal

    private const CSV_COLUMNS = [
        'age', 'gender', 'marital_status', 'education_level', 'employment_status',
        'sleep_hours', 'physical_activity_hours_per_week', 'screen_time_hours_per_day',
        'social_support_score', 'work_stress_level', 'academic_pressure_level',
        'job_satisfaction_score', 'financial_stress_level', 'working_hours_per_week',
        'anxiety_score', 'depression_score', 'stress_level', 'mood_swings_frequency',
        'concentration_difficulty_level', 'panic_attack_history',
        'family_history_mental_illness', 'previous_mental_health_diagnosis',
        'therapy_history', 'substance_use',
    ];

    public function handle(): void
    {
        $startedAt = now();
        Log::info('[RetrainModels] Job dimulai.');

        // --- (a) Ekspor semua predictions ke user_contributions.csv ---
        $predictions = Prediction::select(['input_features', 'final_prediction'])->get();
        $totalRows   = $predictions->count();

        if ($totalRows === 0) {
            Log::warning('[RetrainModels] Tidak ada prediksi, job dibatalkan.');
            return;
        }

        $csvPath = storage_path('models/data/user_contributions.csv');
        $handle  = fopen($csvPath, 'w');

        if ($handle === false) {
            Log::error('[RetrainModels] Gagal membuka file untuk ditulis: ' . $csvPath);
            return;
        }

        // Header: 24 kolom fitur + mental_health_risk
        fputcsv($handle, array_merge(self::CSV_COLUMNS, ['mental_health_risk']));

        foreach ($predictions as $pred) {
            $features = is_array($pred->input_features)
                ? $pred->input_features
                : json_decode($pred->input_features, true);

            $row = [];
            foreach (self::CSV_COLUMNS as $col) {
                $row[] = $features[$col] ?? '';
            }
            $row[] = $pred->final_prediction;

            fputcsv($handle, $row);
        }

        fclose($handle);
        Log::info("[RetrainModels] user_contributions.csv ditulis: {$totalRows} baris.");

        // --- (b) Jalankan train_models.py ---
        $python = env('PYTHON_PATH', 'python');
        $script = storage_path('models/train_models.py');

        $env = $this->buildWindowsEnv();

        $process = new Process([$python, $script], null, $env);
        $process->setTimeout(590);
        $process->run();

        $stdout   = trim($process->getOutput());
        $stderr   = trim($process->getErrorOutput());
        $exitCode = $process->getExitCode();

        if (! $process->isSuccessful()) {
            Log::error('[RetrainModels] train_models.py gagal.', [
                'exit_code' => $exitCode,
                'stderr'    => $stderr,
                'stdout'    => substr($stdout, -500),
            ]);
            return;
        }

        Log::info('[RetrainModels] train_models.py selesai.', ['exit_code' => $exitCode]);

        // --- (c) Update model_metrics dari train_results.json ---
        $resultsPath = storage_path('models/train_results.json');
        if (file_exists($resultsPath)) {
            $metrics = json_decode(file_get_contents($resultsPath), true);
            if (is_array($metrics)) {
                foreach ($metrics as $algorithm => $m) {
                    ModelMetric::updateOrCreate(
                        ['algorithm' => $algorithm],
                        [
                            'accuracy'  => $m['accuracy']  ?? 0,
                            'precision' => $m['precision'] ?? 0,
                            'recall'    => $m['recall']    ?? 0,
                            'f1_score'  => $m['f1_score']  ?? 0,
                        ]
                    );
                }
                Log::info('[RetrainModels] model_metrics diperbarui untuk: ' . implode(', ', array_keys($metrics)));
            }
        }

        $elapsed = now()->diffInSeconds($startedAt);

        // Baca retrain count dari metadata yang baru ditulis oleh train_models.py
        $metaPath = storage_path('models/retrain_metadata.json');
        $retrainCount = null;
        if (file_exists($metaPath)) {
            $meta = json_decode(file_get_contents($metaPath), true);
            $retrainCount = $meta['retrains_count'] ?? null;
        }

        Log::info("[RetrainModels] Selesai dalam {$elapsed}s. Total data user: {$totalRows} baris. Retrain ke-{$retrainCount}.");
    }

    private function buildWindowsEnv(): ?array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $systemRoot  = getenv('SystemRoot') ?: 'C:\\Windows';
        $sys32       = $systemRoot . '\\System32';
        $extraPath   = $sys32 . ';' . $systemRoot . ';' . $sys32 . '\\Wbem;' . $sys32 . '\\WindowsPowerShell\\v1.0';
        $currentPath = getenv('PATH') ?: '';

        return [
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
}
