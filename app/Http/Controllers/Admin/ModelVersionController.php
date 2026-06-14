<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModelVersion;
use App\Services\ModelVersionManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ModelVersionController extends Controller
{
    public function __construct(private ModelVersionManager $manager)
    {
    }

    public function index(): View
    {
        // Versi yang dapat dipilih: aktif + pending.
        $versions = ModelVersion::whereIn('status', [
            ModelVersion::STATUS_ACTIVE,
            ModelVersion::STATUS_PENDING,
        ])->latest()->get();

        // Keberadaan file .pkl per (versi, algo) untuk status hapus/apply di view.
        $presence = [];
        foreach ($versions as $v) {
            $presence[$v->id] = $this->manager->presentAlgos($v);
        }

        return view('admin.models.index', [
            'versions'    => $versions,
            'assignments' => $this->manager->currentAssignments(), // algo => ModelVersion|null
            'algoKeys'    => ModelVersionManager::ALGO_KEYS,
            'metricsKey'  => ModelVersionManager::METRICS_KEY,
            'presence'    => $presence, // [versionId => [algo => bool]]
        ]);
    }

    /**
     * Terapkan algoritma terpilih (checkbox) dari satu versi.
     */
    public function apply(Request $request, ModelVersion $modelVersion): RedirectResponse
    {
        $validated = $request->validate([
            'algos'   => 'required|array|min:1',
            'algos.*' => 'in:' . implode(',', ModelVersionManager::ALGO_KEYS),
        ]);

        $map = [];
        foreach ($validated['algos'] as $algo) {
            $map[$algo] = $modelVersion->id;
        }

        try {
            $this->manager->applyAssignments($map);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $labels = implode(', ', $validated['algos']);

        return back()->with('success', "Algoritma [{$labels}] kini memakai versi #{$modelVersion->id}.");
    }

    /**
     * Hapus artifact algoritma terpilih (checkbox) dari satu versi untuk hemat disk.
     */
    public function destroyAlgos(Request $request, ModelVersion $modelVersion): RedirectResponse
    {
        $validated = $request->validate([
            'algos'   => 'required|array|min:1',
            'algos.*' => 'in:' . implode(',', ModelVersionManager::ALGO_KEYS),
        ]);

        try {
            $this->manager->deleteAlgoArtifacts($modelVersion, $validated['algos']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $labels = implode(', ', $validated['algos']);

        return back()->with('success', "Artifact [{$labels}] dari versi #{$modelVersion->id} dihapus dari disk.");
    }

    public function reject(ModelVersion $modelVersion): RedirectResponse
    {
        try {
            $this->manager->reject($modelVersion);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Versi #{$modelVersion->id} ditolak dan artefaknya dihapus.");
    }
}
