<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('/bot')->group(function () {
    try {
        Route::post('mozgva', [\App\Http\Controllers\Mozgva\MozgvaController::class, 'webhook'])->name('mozgva-webhook');
    } catch (\Exception) {
        return 'NOT OK';
    }

    return 'OK';
});