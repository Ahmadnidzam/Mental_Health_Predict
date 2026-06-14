<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Contracts\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        // User.php tidak boleh diubah (Wave 1), sehingga relasi predictions()
        // tidak tersedia. Attach predictions_count via subquery selectSub.
        $users = User::query()
            ->select('users.*')
            ->selectSub(
                Prediction::selectRaw('count(*)')->whereColumn('predictions.user_id', 'users.id'),
                'predictions_count'
            )
            ->where('is_admin', false)
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $predictions = Prediction::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('admin.users.show', compact('user', 'predictions'));
    }
}
