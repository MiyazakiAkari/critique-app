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

        // 認証試行
        if (!Auth::attempt($request->only('email', 'password'), true)) {
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が正しくありません。'],
            ]);
        }

        // セッションを再生成（セキュリティ強化）
        $request->session()->regenerate();

        // 204 No Content → Cookie による認証が成立
        return response()->noContent();
    }
}
