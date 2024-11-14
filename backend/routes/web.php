<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/device-management', function () {
    $response = new \Illuminate\Http\Response();
    return $response->setContent('Device Management Page');
});

Route::view('/register', 'auth.register');
Route::view('/login', 'auth.login');
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')->middleware('auth:api');
