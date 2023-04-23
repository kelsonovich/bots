<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/parsing', function () {
    \App\Service\Parsing\ParsingService::mozgva();
    \App\Service\Parsing\ParsingService::quizplease();
    \App\Service\PackageWinnerService::start();

    return 'OK';
});

Route::get('/notification', function () {
    \App\Service\NotificationService::prepare();
    \App\Service\NotificationService::send();

    return 'OK';
});



