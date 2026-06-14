<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DataExportController as AdminDataExportController;
use App\Http\Controllers\Admin\ModelVersionController as AdminModelVersionController;
use App\Http\Controllers\Admin\PredictionController as AdminPredictionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Route;

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home')->middleware('admin.only');
Route::get('/models', [ModelController::class, 'index'])->name('models')->middleware('admin.only');
Route::get('/about', function () {
    return view('about');
})->name('about')->middleware('admin.only');

// Protected routes (require login)
Route::middleware('auth')->group(function () {
    Route::get('/predict', [PredictionController::class, 'showForm'])->name('predict.form')->middleware('admin.only');
    Route::post('/predict', [PredictionController::class, 'predict'])->name('predict.store')->middleware('admin.only');
    Route::post('/predict/csv', [PredictionController::class, 'predictCsv'])->name('predict.csv')->middleware('admin.only');
    Route::get('/predict/{prediction}', [PredictionController::class, 'show'])->name('predict.show')->middleware('admin.only');

    Route::get('/history', [HistoryController::class, 'index'])->name('history')->middleware('admin.only');
});

// Admin area (require login + admin flag)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');

    Route::get('/predictions', [AdminPredictionController::class, 'index'])->name('predictions.index');

    Route::get('/models', [AdminModelVersionController::class, 'index'])->name('models.index');
    Route::post('/models/{modelVersion}/apply', [AdminModelVersionController::class, 'apply'])->name('models.apply');
    Route::post('/models/{modelVersion}/algos/delete', [AdminModelVersionController::class, 'destroyAlgos'])->name('models.algos.destroy');
    Route::post('/models/{modelVersion}/reject', [AdminModelVersionController::class, 'reject'])->name('models.reject');

    Route::get('/data/export', [AdminDataExportController::class, 'export'])->name('data.export');
});
