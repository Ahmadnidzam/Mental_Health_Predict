<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModelVersion;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeVersion = ModelVersion::active()->first();
        $latestVersion = ModelVersion::latest()->first();

        $lastRetrain = $activeVersion?->approved_at ?? $latestVersion?->created_at;

        return view('admin.dashboard', [
            'totalUsers'       => User::count(),
            'totalPredictions' => Prediction::count(),
            'activeVersion'    => $activeVersion,
            'pendingCount'     => ModelVersion::pending()->count(),
            'lastRetrain'      => $lastRetrain,
            'datasetSize'      => $activeVersion?->dataset_size,
        ]);
    }
}
