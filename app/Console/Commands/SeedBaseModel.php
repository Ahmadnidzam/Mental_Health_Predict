<?php

namespace App\Console\Commands;

use App\Models\ModelVersion;
use App\Services\ModelVersionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SeedBaseModel extends Command
{
    protected $signature = 'models:seed-base {--force : Latih ulang base meski sudah ada versi aktif}';

    protected $description = 'Latih model base (dataset dasar saja), buat ModelVersion aktif, dan sinkronkan metrik.';

    public function handle(ModelVersionManager $manager): int
    {
        if (! $this->option('force') && ModelVersion::active()->exists()) {
            $this->warn('Sudah ada versi model aktif. Gunakan --force untuk melatih ulang base.');
            return self::FAILURE;
        }

        $version = ModelVersion::create([
            'status' => ModelVersion::STATUS_PENDING,
            'label'  => 'Base',
        ]);

        $dir = storage_path('models/versions/' . $version->id);
        File::ensureDirectoryExists($dir);

        $this->info("Melatih model base ke {$dir} ...");

        $result = $this->runTraining($dir);

        if ($result['exit_code'] !== 0) {
            File::deleteDirectory($dir);
            $version->delete();
            $this->error('Pelatihan gagal:');
            $this->error($result['stderr'] ?: 'Tidak ada output stderr.');
            return self::FAILURE;
        }

        $metrics = $this->readMetrics($dir);

        $version->update([
            'metrics'        => $metrics,
            'dataset_size'   => $metrics['dataset_size'] ?? $this->baseDatasetRowCount(),
            'user_rows_used' => 0,
            'artifact_path'  => 'versions/' . $version->id,
        ]);

        // metrics.json mungkin menaruh dataset_size di dalam dirinya; bersihkan
        // agar kolom metrics hanya berisi metrik per-algoritma (skema C3).
        unset($metrics['dataset_size']);
        $version->update(['metrics' => $metrics]);

        $manager->activateAll($version);

        $this->info("Model base aktif sebagai ModelVersion #{$version->id}.");
        return self::SUCCESS;
    }

    /**
     * @return array{exit_code:int,stdout:string,stderr:string}
     */
    private function runTraining(string $outputDir): array
    {
        $python = config('services.python.path', 'python');
        $script = storage_path('models/train_models.py');

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $command = [$python, $script, '--base-only', '--output-dir', $outputDir];

        $maxRows = (int) config('services.train.max_rows', 0);
        if ($maxRows > 0) {
            $command[] = '--max-rows';
            $command[] = (string) $maxRows;
        }

        $proc = proc_open($command, $descriptors, $pipes, null, null);

        if (! is_resource($proc)) {
            return ['exit_code' => 1, 'stdout' => '', 'stderr' => 'Gagal membuka Python process.'];
        }

        fclose($pipes[0]);
        $stdout = trim(stream_get_contents($pipes[1]));
        $stderr = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        return ['exit_code' => $exitCode, 'stdout' => $stdout, 'stderr' => $stderr];
    }

    private function readMetrics(string $dir): array
    {
        $path = $dir . DIRECTORY_SEPARATOR . 'metrics.json';
        if (! File::exists($path)) {
            return [];
        }

        $decoded = json_decode(File::get($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function baseDatasetRowCount(): int
    {
        $path = storage_path('models/data/mental_health_risk_dataset.csv');
        if (! File::exists($path)) {
            return 0;
        }

        $count = 0;
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return 0;
        }

        while (fgetcsv($handle) !== false) {
            $count++;
        }
        fclose($handle);

        // Kurangi baris header.
        return max(0, $count - 1);
    }
}
