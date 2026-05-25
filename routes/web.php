<?php

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
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/models', [ModelController::class, 'index'])->name('models');
Route::get('/about', function () {
    return view('about');
})->name('about');

// Protected routes (require login)
Route::middleware('auth')->group(function () {
    Route::get('/predict', [PredictionController::class, 'showForm'])->name('predict.form');
    Route::post('/predict', [PredictionController::class, 'predict'])->name('predict.store');
    Route::post('/predict/csv', [PredictionController::class, 'predictCsv'])->name('predict.csv');
    Route::get('/predict/{prediction}', [PredictionController::class, 'show'])->name('predict.show');

    Route::get('/history', [HistoryController::class, 'index'])->name('history');
});
