<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ServerController;
use App\Http\Controllers\DemoController;

Route::redirect('/', '/servidor');
Route::get('/servidor', [ServerController::class, 'index']);
Route::get('/api/metrics', [ServerController::class, 'metrics']);
Route::get('/graficos', [ServerController::class, 'charts']);
Route::post('/demo/open', [DemoController::class, 'open'])->middleware('web');
