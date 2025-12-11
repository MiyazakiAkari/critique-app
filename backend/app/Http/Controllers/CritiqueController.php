<?php

namespace App\Http\Controllers;

use App\Models\Critique;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CritiqueController extends Controller
{
    /**
     * 特定の投稿に対する添削一覧を取得
     */
    public function index(Post $post): JsonResponse
    {
        $critiques = $post->critiques()
            ->with('user:id,name,username')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($critique) {
                return [
                    'id' => $critique->id,
                    'content' => $critique->content,
                    'created_at' => $critique->created_at->toISOString(),
                    'user' => $critique->user->only(['id', 'name', 'username']),
                ];
            });

        return response()->json($critiques, 200);
    }

    /**
     * 新規添削を作成
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $critique = $post->critiques()->create([
            'user_id' => $user->id,
            'content' => $validated['content'],
        ]);

        $critique->load('user:id,name,username');

        return response()->json([
            'id' => $critique->id,
            'content' => $critique->content,
            'created_at' => $critique->created_at->toISOString(),
            'user' => $critique->user->only(['id', 'name', 'username']),
        ], 201);
    }

    /**
     * 添削を削除
     */
    public function destroy(Post $post, Critique $critique): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 添削が指定された投稿に属していることを確認
        if ($critique->post_id !== $post->id) {
            return response()->json([
                'message' => '添削が見つかりません',
            ], 404);
        }

        // 自分の添削のみ削除可能
        if ($critique->user_id !== $user->id) {
            return response()->json([
                'message' => '削除権限がありません',
            ], 403);
        }

        $critique->delete();

        return response()->json(null, 204);
    }
}
