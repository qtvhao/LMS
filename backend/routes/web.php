<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/register', 'auth.register');
Route::view('/login', 'auth.login');
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')->middleware('auth:api');
