<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * name / username でユーザーを検索する（部分一致）
     * ※ id は検索条件に含めない
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:50',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $limit = $validated['limit'] ?? 20;
        $keyword = $validated['keyword'];
        
        // LIKE クエリのワイルドカード文字をエスケープ
        $escapedKeyword = addslashes($keyword);

        $users = User::query()
            ->select('id', 'name', 'username')
            ->where(function ($query) use ($escapedKeyword) {
                $query->where('username', 'like', '%' . $escapedKeyword . '%')
                    ->orWhere('name', 'like', '%' . $escapedKeyword . '%');
            })
            ->orderBy('username')
            ->limit($limit)
            ->get();

        return response()->json([
            'users' => $users,
            'count' => $users->count(),
        ], 200);
    }
}

