<?php

namespace App\Http\Controllers;

use App\Models\Prediction;

class HistoryController extends Controller
{
    /**
     * Tampilkan daftar semua prediksi.
     */
    public function index()
    {
        $predictions = Prediction::latest()->paginate(15);

        return view('history', compact('predictions'));
    }
}
