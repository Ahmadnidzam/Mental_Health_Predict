<?php

namespace App\Http\Controllers;

use App\Models\ModelMetric;
use App\Models\Prediction;

class HomeController extends Controller
{
    private const BASE_DATASET_SIZE = 25000;

    public function index()
    {
        $totalPredictions = Prediction::count();
        $bestModel        = ModelMetric::orderByDesc('accuracy')->first();

        // Baca retrain_metadata.json untuk dataset size yang akurat
        $retrainMeta  = $this->readRetrainMeta();
        $datasetSize  = $retrainMeta['last_retrain_dataset_size'] ?? self::BASE_DATASET_SIZE;
        $lastRetrain  = $retrainMeta['last_retrain_timestamp']    ?? null;
        $retrainCount = $retrainMeta['retrains_count']            ?? 0;
        $userRows     = $retrainMeta['user_data_rows_used']       ?? 0;

        return view('home', [
            'totalPredictions' => $totalPredictions,
            'bestModel'        => $bestModel,
            'datasetSize'      => $datasetSize,
            'lastRetrain'      => $lastRetrain,
            'retrainCount'     => $retrainCount,
            'userRows'         => $userRows,
        ]);
    }

    private function readRetrainMeta(): array
    {
        $path = storage_path('models/retrain_metadata.json');
        if (! file_exists($path)) {
            return [];
        }
        $decoded = json_decode(file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }
}
