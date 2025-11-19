<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        // セッション破棄
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
