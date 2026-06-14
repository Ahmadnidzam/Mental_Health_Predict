<?php

namespace App\Services;

use App\Models\ActiveModel;
use App\Models\ModelMetric;
use App\Models\ModelVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ModelVersionManager
{
    /** Key algoritma (lowercase) yang dipakai di active_models + pointer + predict.py. */
    public const ALGO_KEYS = ['knn', 'knn_hpo', 'svm', 'svm_hpo', 'dt', 'dt_hpo'];

    /** Map key algo -> key metrics (di metrics.json / model_metrics). */
    public const METRICS_KEY = [
        'knn'     => 'KNN',
        'knn_hpo' => 'KNN+HPO',
        'svm'     => 'SVM',
        'svm_hpo' => 'SVM+HPO',
        'dt'      => 'DT',
        'dt_hpo'  => 'DT+HPO',
    ];

    /** Nama file .pkl per algoritma di dalam direktori versi. */
    public const MODEL_FILE = [
        'knn'     => 'knn_model.pkl',
        'knn_hpo' => 'knn_hpo_model.pkl',
        'svm'     => 'svm_model.pkl',
        'svm_hpo' => 'svm_hpo_model.pkl',
        'dt'      => 'dt_model.pkl',
        'dt_hpo'  => 'dt_hpo_model.pkl',
    ];

    /**
     * Jadikan SATU versi sumber untuk SEMUA algoritma (dipakai seed-base & backfill).
     * Tidak menuntut status pending (boleh dipanggil untuk versi aktif saat backfill).
     */
    public function activateAll(ModelVersion $version): void
    {
        DB::transaction(function () use ($version): void {
            foreach (self::ALGO_KEYS as $algo) {
                ActiveModel::updateOrCreate(
                    ['algorithm' => $algo],
                    ['model_version_id' => $version->id],
                );
            }

            if ($version->status === ModelVersion::STATUS_PENDING) {
                $version->forceFill([
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ])->save();
            }

            $this->writePointer();
            $this->recomputeStatuses();
            $this->syncMetrics(self::ALGO_KEYS);
        });
    }

    /**
     * Terapkan assignment per-algoritma: [algoKey => versionId, ...].
     * Hanya algoritma yang disebut yang diubah; sisanya tetap.
     *
     * @param array<string,int> $map
     * @throws RuntimeException bila versi tidak valid / artifact algo tidak ada.
     */
    public function applyAssignments(array $map): void
    {
        if (empty($map)) {
            throw new RuntimeException('Tidak ada algoritma yang dipilih.');
        }

        DB::transaction(function () use ($map): void {
            foreach ($map as $algo => $versionId) {
                if (! in_array($algo, self::ALGO_KEYS, true)) {
                    throw new RuntimeException("Algoritma tidak dikenal: {$algo}");
                }

                $version = ModelVersion::findOrFail($versionId);

                // Hanya versi aktif/pending yang boleh dipilih.
                if (! in_array($version->status, [ModelVersion::STATUS_ACTIVE, ModelVersion::STATUS_PENDING], true)) {
                    throw new RuntimeException("Versi #{$versionId} tidak dapat dipilih (status {$version->status}).");
                }

                // Pastikan artifact algoritma ada di direktori versi.
                $this->assertArtifactExists($version, $algo);

                ActiveModel::updateOrCreate(
                    ['algorithm' => $algo],
                    ['model_version_id' => $version->id],
                );

                // Versi pending yang baru dirujuk → tandai waktu approve.
                if ($version->status === ModelVersion::STATUS_PENDING && $version->approved_at === null) {
                    $version->forceFill([
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                    ])->save();
                }
            }

            $this->writePointer();
            $this->recomputeStatuses();
            $this->syncMetrics(array_keys($map));
        });
    }

    /**
     * Tolak versi: hanya bila TIDAK dirujuk algoritma manapun. Hapus artifact + set rejected.
     *
     * @throws RuntimeException bila versi masih dirujuk.
     */
    public function reject(ModelVersion $version): void
    {
        if ($this->isReferenced($version)) {
            throw new RuntimeException("Versi #{$version->id} masih dipakai oleh minimal satu algoritma. Alihkan dulu sebelum menolak.");
        }

        if ($version->artifact_path) {
            $dir = storage_path('models/' . $version->artifact_path);
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
        }

        $version->update([
            'status'        => ModelVersion::STATUS_REJECTED,
            'artifact_path' => null,
        ]);
    }

    /** Apakah versi sedang dirujuk minimal satu algoritma di active_models. */
    public function isReferenced(ModelVersion $version): bool
    {
        return ActiveModel::where('model_version_id', $version->id)->exists();
    }

    /**
     * Hapus artifact .pkl per-algoritma dari sebuah versi untuk menghemat disk.
     * File shared (scaler/freq_maps/feature_columns) hanya dihapus saat folder
     * tak menyisakan satupun model algoritma (auto-purge).
     *
     * Guard: algoritma yang SEDANG aktif untuk versi ini tak boleh dihapus —
     * alihkan dulu agar inferensi tidak putus.
     *
     * @param array<int,string> $algos
     * @throws RuntimeException bila versi tanpa artifact, algo tak dikenal, atau algo masih aktif.
     */
    public function deleteAlgoArtifacts(ModelVersion $version, array $algos): void
    {
        if (empty($algos)) {
            throw new RuntimeException('Tidak ada algoritma yang dipilih untuk dihapus.');
        }

        if (! $version->artifact_path) {
            throw new RuntimeException("Versi #{$version->id} tidak punya artifact untuk dihapus.");
        }

        $assignments = $this->currentAssignments();
        $dir = storage_path('models/' . $version->artifact_path);

        foreach ($algos as $algo) {
            if (! in_array($algo, self::ALGO_KEYS, true)) {
                throw new RuntimeException("Algoritma tidak dikenal: {$algo}");
            }

            // Algo yang sedang aktif untuk versi ini → blokir.
            if (($assignments[$algo] ?? null)?->id === $version->id) {
                throw new RuntimeException(
                    "Algoritma {$algo} sedang aktif dari versi #{$version->id}. Alihkan dulu sebelum menghapus."
                );
            }
        }

        foreach ($algos as $algo) {
            $file = $dir . DIRECTORY_SEPARATOR . self::MODEL_FILE[$algo];
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Auto-purge: bila tak ada lagi model algoritma tersisa DAN versi tak
        // dirujuk algoritma manapun, hapus seluruh folder + tandai rejected.
        if (! $this->hasAnyAlgoArtifact($version) && ! $this->isReferenced($version)) {
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
            $version->update([
                'status'        => ModelVersion::STATUS_REJECTED,
                'artifact_path' => null,
            ]);
        }
    }

    /**
     * Status keberadaan file .pkl tiap algoritma pada sebuah versi.
     *
     * @return array<string,bool> [algoKey => ada?]
     */
    public function presentAlgos(ModelVersion $version): array
    {
        $out = [];
        foreach (self::ALGO_KEYS as $algo) {
            $out[$algo] = $this->algoArtifactExists($version, $algo);
        }

        return $out;
    }

    private function algoArtifactExists(ModelVersion $version, string $algo): bool
    {
        if (! $version->artifact_path) {
            return false;
        }

        return File::exists(
            storage_path('models/' . $version->artifact_path . '/' . self::MODEL_FILE[$algo])
        );
    }

    private function hasAnyAlgoArtifact(ModelVersion $version): bool
    {
        foreach (self::ALGO_KEYS as $algo) {
            if ($this->algoArtifactExists($version, $algo)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assignment aktif saat ini: [algoKey => ModelVersion].
     *
     * @return array<string,ModelVersion>
     */
    public function currentAssignments(): array
    {
        $rows = ActiveModel::with('modelVersion')->get()->keyBy('algorithm');

        $out = [];
        foreach (self::ALGO_KEYS as $algo) {
            $out[$algo] = $rows->get($algo)?->modelVersion;
        }

        return $out;
    }

    private function assertArtifactExists(ModelVersion $version, string $algo): void
    {
        if (! $version->artifact_path) {
            throw new RuntimeException("Versi #{$version->id} tidak punya artifact_path.");
        }

        $file = storage_path('models/' . $version->artifact_path . '/' . self::MODEL_FILE[$algo]);
        if (! File::exists($file)) {
            throw new RuntimeException("Artifact {$algo} tidak ditemukan di versi #{$version->id}.");
        }
    }

    /**
     * Tulis active_version.json sebagai map per-algoritma dari active_models.
     */
    private function writePointer(): void
    {
        $assignments = $this->currentAssignments();

        $pointer = [];
        foreach (self::ALGO_KEYS as $algo) {
            $version = $assignments[$algo] ?? null;
            if ($version && $version->artifact_path) {
                $pointer[$algo] = [
                    'version_id' => $version->id,
                    'path'       => $version->artifact_path,
                ];
            }
        }

        File::put(
            storage_path('models/active_version.json'),
            json_encode($pointer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Recompute status versi berdasarkan referensi active_models:
     * dirujuk → active; tak-dirujuk tapi sebelumnya active → archived.
     * pending/rejected tidak disentuh.
     */
    private function recomputeStatuses(): void
    {
        $referencedIds = ActiveModel::query()->distinct()->pluck('model_version_id')->all();

        // Versi yang dirujuk → active.
        if (! empty($referencedIds)) {
            ModelVersion::whereIn('id', $referencedIds)
                ->where('status', '!=', ModelVersion::STATUS_ACTIVE)
                ->update(['status' => ModelVersion::STATUS_ACTIVE]);
        }

        // Versi yang dulu active tapi kini tak dirujuk → archived.
        ModelVersion::where('status', ModelVersion::STATUS_ACTIVE)
            ->when(! empty($referencedIds), fn ($q) => $q->whereNotIn('id', $referencedIds))
            ->update(['status' => ModelVersion::STATUS_ARCHIVED]);
    }

    /**
     * Sinkronkan model_metrics untuk algoritma tertentu dari versi aktifnya
     * masing-masing. Baris metrik mencerminkan akurasi algoritma yang dipakai.
     *
     * @param array<int,string> $algoKeys
     */
    private function syncMetrics(array $algoKeys): void
    {
        $assignments = $this->currentAssignments();

        foreach ($algoKeys as $algo) {
            $version    = $assignments[$algo] ?? null;
            $metricsKey = self::METRICS_KEY[$algo] ?? null;
            if (! $version || ! $metricsKey) {
                continue;
            }

            $m = $version->metrics[$metricsKey] ?? null;
            if (! is_array($m)) {
                continue;
            }

            ModelMetric::updateOrCreate(
                ['algorithm' => $metricsKey],
                [
                    'accuracy'  => $m['accuracy']  ?? 0,
                    'precision' => $m['precision'] ?? 0,
                    'recall'    => $m['recall']    ?? 0,
                    'f1_score'  => $m['f1_score']  ?? 0,
                ]
            );
        }
    }
}
