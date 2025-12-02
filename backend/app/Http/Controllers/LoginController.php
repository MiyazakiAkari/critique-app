<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // バリデーション
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 認証試行（webガードを明示的に使用）
        if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が正しくありません。'],
            ]);
        }

        $user = Auth::guard('web')->user();

        // 既存のパーソナルアクセストークンを削除して一人一枚のトークンに制限
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only(['id', 'name', 'username', 'email']),
        ]);
    }
}
