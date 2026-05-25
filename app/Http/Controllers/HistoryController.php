<?php

namespace App\Http\Controllers;

use App\Models\Prediction;

class HistoryController extends Controller
{
    public function index()
    {
        $predictions = Prediction::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('history', compact('predictions'));
    }
}
