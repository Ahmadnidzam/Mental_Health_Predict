<?php

namespace App\Jobs;

use App\Models\ModelVersion;
use App\Models\Prediction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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

    /**
     * Serialkan trigger retrain yang berbarengan — job kedua menunggu/retry,
     * bukan jalan paralel dengan yang pertama.
     */
    public function middleware(): array
    {
        // releaseAfter: bila ada job retrain lain yang sedang berjalan, lepaskan
        // job ini kembali ke antrean dan coba lagi setelah 60 detik (bukan loop ketat).
        return [(new WithoutOverlapping('retrain-models'))->releaseAfter(60)];
    }

    public function handle(): void
    {
        $startedAt   = now();
        $countAtStart = Prediction::count();
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

        // --- (b) Buat versi model baru berstatus pending ---
        $retrainNumber = ModelVersion::count() + 1;
        $version = ModelVersion::create([
            'status' => ModelVersion::STATUS_PENDING,
            'label'  => 'Retrain #' . $retrainNumber,
        ]);

        $dir = storage_path('models/versions/' . $version->id);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        $version->artifact_path = 'versions/' . $version->id;

        // --- (c) Jalankan train_models.py --output-dir <dir> (tanpa --base-only,
        // sehingga user_contributions ikut digabung) via proc_open
        // (null env = OS-level inherit, hindari batas argv Windows). ---
        $python = config('services.python.path', 'python');
        $script = storage_path('models/train_models.py');

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $command = [$python, $script, '--output-dir', $dir];

        $maxRows = (int) config('services.train.max_rows', 0);
        if ($maxRows > 0) {
            $command[] = '--max-rows';
            $command[] = (string) $maxRows;
        }

        $proc = proc_open($command, $descriptors, $pipes, null, null);

        if (! is_resource($proc)) {
            Log::error('[RetrainModels] Gagal membuka Python process.');
            File::deleteDirectory($dir);
            $version->delete();
            return;
        }

        fclose($pipes[0]);
        $stdout   = trim(stream_get_contents($pipes[1]));
        $stderr   = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        $metricsPath = $dir . DIRECTORY_SEPARATOR . 'metrics.json';

        if ($exitCode !== 0 || ! file_exists($metricsPath)) {
            Log::error('[RetrainModels] train_models.py gagal atau metrics.json tidak ditemukan.', [
                'exit_code'      => $exitCode,
                'metrics_exists' => file_exists($metricsPath),
                'stderr'         => substr($stderr, -500),
                'stdout'         => substr($stdout, -500),
            ]);
            File::deleteDirectory($dir);
            $version->delete();
            return;
        }

        // --- (d) Sukses: baca metrics.json, isi metadata versi. ---
        // Job ini TIDAK menulis active_version.json dan TIDAK menyentuh tabel
        // model_metrics — versi pending belum diterapkan.
        $metrics = json_decode(file_get_contents($metricsPath), true);
        if (! is_array($metrics)) {
            Log::error('[RetrainModels] metrics.json tidak valid.', ['metrics_path' => $metricsPath]);
            File::deleteDirectory($dir);
            $version->delete();
            return;
        }

        // user_rows_used = jumlah baris data (tanpa header) di user_contributions.csv.
        $userRowsUsed = $totalRows;

        // dataset_size: pakai dari metrics.json bila ada, jika tidak hitung dari
        // gabungan (baris base csv + baris user_contributions, masing-masing tanpa header).
        $datasetSize = $metrics['dataset_size'] ?? null;
        if ($datasetSize === null) {
            $baseRows = $this->countCsvDataRows(storage_path('models/data/mental_health_risk_dataset.csv'));
            $datasetSize = $baseRows + $userRowsUsed;
        }

        $version->metrics        = $metrics;
        $version->dataset_size   = (int) $datasetSize;
        $version->user_rows_used = $userRowsUsed;
        $version->save();

        $elapsed = now()->diffInSeconds($startedAt);
        $bestAcc = $version->bestAccuracy();
        Log::info(sprintf(
            '[RetrainModels] Selesai dalam %ds. Versi #%d (%s) dibuat sebagai pending. Akurasi terbaik: %s',
            $elapsed,
            $version->id,
            $version->label,
            $bestAcc === null ? 'n/a' : number_format($bestAcc, 4)
        ));

        // --- (e) Re-check trigger: bila jumlah prediksi telah melewati ambang
        // berikutnya sejak job dimulai DAN belum ada retrain pending lain yang
        // mengantre untuk ambang itu, dispatch lagi. Guard sederhana, hindari loop. ---
        $this->maybeRedispatch($countAtStart);
    }

    /**
     * Hitung jumlah baris data (tanpa header) sebuah file CSV. 0 jika tidak ada.
     */
    private function countCsvDataRows(string $path): int
    {
        if (! file_exists($path)) {
            return 0;
        }

        $lines = 0;
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return 0;
        }
        while (fgets($handle) !== false) {
            $lines++;
        }
        fclose($handle);

        return max(0, $lines - 1); // kurangi baris header
    }

    /**
     * Dispatch ulang job hanya bila jumlah prediksi telah melewati batas
     * kelipatan berikutnya sejak job ini mulai (mirror logika controller).
     */
    private function maybeRedispatch(int $countAtStart): void
    {
        $retrainEvery = (int) config('services.retrain.every', 50);
        if ($retrainEvery <= 0) {
            return;
        }

        $now = Prediction::count();

        // Hanya redispatch bila count benar-benar tumbuh melewati batas berikutnya.
        if ($now <= $countAtStart) {
            return;
        }
        if (floor($now / $retrainEvery) <= floor($countAtStart / $retrainEvery)) {
            return;
        }

        self::dispatch();
        Log::info("[RetrainModels] Re-dispatch — prediksi bertambah dari {$countAtStart} ke {$now}.");
    }
}
