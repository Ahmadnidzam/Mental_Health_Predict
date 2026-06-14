<?php

namespace Database\Seeders;

use App\Models\ModelMetric;
use Illuminate\Database\Seeder;

class ModelMetricSeeder extends Seeder
{
    /**
     * Membaca train_results.json dari storage/models/ dan
     * menyimpan / memperbarui metrik di tabel model_metrics.
     */
    public function run(): void
    {
        $jsonPath = storage_path('models/train_results.json');

        if (! file_exists($jsonPath)) {
            $this->command->error("File {$jsonPath} tidak ditemukan. Jalankan dulu: python storage/models/train_models.py");
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (! is_array($data)) {
            $this->command->error('train_results.json tidak valid.');
            return;
        }

        foreach ($data as $algorithm => $metrics) {
            ModelMetric::updateOrCreate(
                ['algorithm' => $algorithm],
                [
                    'accuracy'  => $metrics['accuracy']  ?? 0,
                    'precision' => $metrics['precision'] ?? 0,
                    'recall'    => $metrics['recall']    ?? 0,
                    'f1_score'  => $metrics['f1_score']  ?? 0,
                ]
            );
            $this->command->info("Saved metric: {$algorithm} (acc=" . number_format(($metrics['accuracy'] ?? 0) * 100, 2) . "%)");
        }

        // Buang baris yang tidak ada di train_results.json (sisa key lama).
        $pruned = ModelMetric::whereNotIn('algorithm', array_keys($data))->delete();
        if ($pruned) {
            $this->command->warn("{$pruned} baris metrik usang dibuang.");
        }

        $this->command->info("Total metrik tersimpan: " . ModelMetric::count());
    }
}
