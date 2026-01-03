<?php

use App\Http\Controllers\CritiqueController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\BestCritiqueController;
use Illuminate\Support\Facades\Route; // Import Route
use Illuminate\Http\Request; // Import Request
use Illuminate\Support\Facades\DB;

// ヘルスチェックエンドポイント（AWS / ロードバランサー用）
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ], 500);
    }
});

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LogoutController::class, 'logout'])->middleware('auth:sanctum');

// ログイン中ユーザー取得
Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');

// ユーザー検索
Route::get('/users/search', [UserController::class, 'search']);

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

// 投稿関連
Route::get('/posts/recommended', [PostController::class, 'recommended']);
Route::get('/users/{username}/posts', [PostController::class, 'userPosts']);

// 投稿操作（認証必須）
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/posts/timeline', [PostController::class, 'timeline']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    
    // リポスト操作
    Route::post('/posts/{post}/repost', [PostController::class, 'repost']);
    Route::delete('/posts/{post}/repost', [PostController::class, 'unrepost']);
});

Route::get('/posts/{post}', [PostController::class, 'show']);

// 添削関連（閲覧は認証不要）
Route::get('/posts/{post}/critiques', [CritiqueController::class, 'index']);

// 添削操作（認証必須）
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/posts/{post}/critiques', [CritiqueController::class, 'store']);
    Route::delete('/posts/{post}/critiques/{critique}', [CritiqueController::class, 'destroy']);
    
    // 添削いいね操作
    Route::post('/posts/{post}/critiques/{critique}/like', [CritiqueController::class, 'like']);
    Route::delete('/posts/{post}/critiques/{critique}/like', [CritiqueController::class, 'unlike']);
});

// Stripe決済関連（認証必須）
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/stripe/confirm-payment', [StripeController::class, 'confirmPayment']);
    Route::get('/stripe/payment-history', [StripeController::class, 'getPaymentHistory']);
});

// ベスト添削選択（認証必須）
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/posts/{post}/best-critique', [BestCritiqueController::class, 'selectBestCritique']);
});
