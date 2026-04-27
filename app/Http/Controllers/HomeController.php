<?php

namespace App\Http\Controllers;

use App\Models\ModelMetric;
use App\Models\Prediction;

class HomeController extends Controller
{
    /**
     * Tampilkan landing page.
     */
    public function index()
    {
        $totalPredictions = Prediction::count();
        $bestModel = ModelMetric::orderByDesc('accuracy')->first();

        return view('home', [
            'totalPredictions' => $totalPredictions,
            'bestModel'        => $bestModel,
        ]);
    }
}
