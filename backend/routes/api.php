<?php

use App\Http\Controllers\FollowController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route; // Import Route
use Illuminate\Http\Request; // Import Request

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LogoutController::class, 'logout'])->middleware('auth:sanctum');

// ログイン中ユーザー取得
Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');

// プロフィール関連
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile/me', [ProfileController::class, 'me']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);
});

// 公開プロフィール（認証不要）
Route::get('/profile/{username}', [ProfileController::class, 'show']);

// フォロー関連（認証不要で閲覧可能）
Route::get('/users/{username}/followers', [FollowController::class, 'followers']);
Route::get('/users/{username}/followings', [FollowController::class, 'followings']);
Route::get('/users/{username}/follow-status', [FollowController::class, 'status']);

// フォロー操作（認証必須）
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/{username}/follow', [FollowController::class, 'follow']);
    Route::delete('/users/{username}/follow', [FollowController::class, 'unfollow']);
});
