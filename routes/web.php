<?php

use App\Enum\GameType;
use App\Models\Schedule;
use Carbon\Carbon;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
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


Route::get('/test', [\App\Http\Controllers\Mozgva\MozgvaController::class, 'test'])->name('mozgva-test');

//Route::get('/test', function () {
////    \App\Service\Parsing\ParsingService::test();
//
//
////    return 'OK';
//});

