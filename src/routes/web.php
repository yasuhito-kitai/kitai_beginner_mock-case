<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StampController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::middleware('auth','verified')->group(function () {Route::get('/stamp', [StampController::class, 'stamp']);});
Route::middleware('auth', 'verified')->group(function () {Route::get('/date', [StampController::class, 'date']);});
Route::middleware('auth', 'verified')->group(function () {Route::get('/user', [StampController::class, 'user']);});
Route::middleware('auth', 'verified')->group(function () {Route::get('/month', [StampController::class, 'month']);});

Route::post('/clock_in', [StampController::class, 'clock_in']);
Route::patch('/clock_out', [StampController::class, 'clock_out']);
Route::post('/break_start', [StampController::class, 'break_start']);
Route::patch('/break_end', [StampController::class, 'break_end']);

Route::get('/before_day', [StampController::class, 'before_day']);
Route::get('/next_day', [StampController::class, 'next_day']);
Route::get('/calendar', [StampController::class, 'calendar']);

Route::get('/user_search', [StampController::class, 'user_search']);

Route::get('/before_month', [StampController::class, 'before_month']);
Route::get('/next_month', [StampController::class, 'next_month']);

//メール確認リンクをクリックするようにユーザーに指示するビューを返すルート
Route::get('/email/verify', function () {
    return view('auth.verify_email');
})->middleware('auth')->name('verification.notice');

//電子メール確認リンクをクリックしたときに生成されるリクエストを処理するルート
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/stamp');
})->middleware(['auth', 'signed'])->name('verification.verify');


//メール確認の再送信
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', '認証メールを再送信しました');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

