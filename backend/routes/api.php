<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route; // Import Route
use Illuminate\Http\Request; // Import Request

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LogoutController::class, 'logout'])->middleware('auth:sanctum');

// ログイン中ユーザー取得
Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');
