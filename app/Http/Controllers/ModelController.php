<?php

namespace App\Http\Controllers;

use App\Models\ModelMetric;

class ModelController extends Controller
{
    /**
     * Tampilkan info & metrik tiap algoritma ML.
     */
    public function index()
    {
        // Ambil metric terbaru per algoritma
        $metrics = ModelMetric::orderBy('algorithm')->get();

        return view('model-info', compact('metrics'));
    }
}
