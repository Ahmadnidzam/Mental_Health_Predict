<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Contracts\View\View;

class PredictionController extends Controller
{
    public function index(): View
    {
        $predictions = Prediction::with('user')
            ->when(request('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('admin.predictions.index', [
            'predictions'    => $predictions,
            'users'          => $users,
            'selectedUserId' => request('user_id'),
        ]);
    }
}
