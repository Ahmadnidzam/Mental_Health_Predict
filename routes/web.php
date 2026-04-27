<?php

use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/predict', [PredictionController::class, 'showForm'])->name('predict.form');
Route::post('/predict', [PredictionController::class, 'predict'])->name('predict.store');
Route::get('/predict/{prediction}', [PredictionController::class, 'show'])->name('predict.show');

Route::get('/history', [HistoryController::class, 'index'])->name('history');

Route::get('/models', [ModelController::class, 'index'])->name('models');

Route::get('/about', function () {
    return view('about');
})->name('about');
