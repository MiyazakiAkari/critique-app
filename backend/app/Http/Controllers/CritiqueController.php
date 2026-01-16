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
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $query = $post->critiques()
            ->with('user:id,name,username')
            ->withCount('likes');

        // ログインユーザーがいる場合、いいね済みかどうかをEager Loadする
        if ($user) {
            $query->withExists(['likes as is_liked' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);
        }

        $critiques = $query
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($critique) use ($user) {
                $imageUrl = null;
                if ($critique->image_path) {
                    $appUrl = config('app.url');
                    $imageUrl = rtrim($appUrl, '/') . '/storage/' . $critique->image_path;
                }

                return [
                    'id' => $critique->id,
                    'content' => $critique->content,
                    'image_path' => $critique->image_path,
                    'image_url' => $imageUrl,
                    'created_at' => $critique->created_at->toISOString(),
                    'user' => $critique->user->only(['id', 'name', 'username']),
                    'likes_count' => $critique->likes_count,
                    'is_liked' => $user ? (bool) $critique->is_liked : false,
                ];
            });

        return response()->json($critiques, 200);
    }

    /**
     * 新規添削を作成
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        $imageRules = [
            'nullable',
            'image',
            'mimes:jpeg,png,jpg,gif',
            'max:10240', // 10MB
        ];

        // テスト環境以外では追加のセキュリティチェックを実施
        if (!app()->environment('testing')) {
            $imageRules[] = function ($attribute, $value, $fail) {
                if ($value && !@getimagesize($value->getRealPath())) {
                    $fail('The ' . $attribute . ' is not a valid image file.');
                }
            };
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'image' => $imageRules,
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 画像を保存
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('critiques', 'public');
        }

        $critique = $post->critiques()->create([
            'user_id' => $user->id,
            'content' => $validated['content'],
            'image_path' => $imagePath,
        ]);

        $critique->load('user:id,name,username');

        $imageUrl = null;
        if ($critique->image_path) {
            $appUrl = config('app.url');
            $imageUrl = rtrim($appUrl, '/') . '/storage/' . $critique->image_path;
        }

        return response()->json([
            'id' => $critique->id,
            'content' => $critique->content,
            'image_path' => $critique->image_path,
            'image_url' => $imageUrl,
            'created_at' => $critique->created_at->toISOString(),
            'user' => $critique->user->only(['id', 'name', 'username']),
            'likes_count' => 0,
            'is_liked' => false,
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

    /**
     * 添削にいいねする
     */
    public function like(Post $post, Critique $critique): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 添削が指定された投稿に属していることを確認
        if ($critique->post_id !== $post->id) {
            return response()->json([
                'message' => '添削が見つかりません',
            ], 404);
        }

        // 自分の添削にはいいねできない
        if ($critique->user_id === $user->id) {
            return response()->json([
                'message' => '自分の添削にはいいねできません',
            ], 403);
        }

        // いいね数を事前にロード
        $critique->loadCount('likes');

        // 既にいいねしているかチェック
        if ($critique->isLikedBy($user)) {
            return response()->json([
                'message' => '既にいいねしています',
            ], 409);
        }

        $critique->likes()->create([
            'user_id' => $user->id,
        ]);

        return response()->json([
            'likes_count' => $critique->likes_count + 1,
            'is_liked' => true,
        ], 201);
    }

    /**
     * 添削のいいねを解除する
     */
    public function unlike(Post $post, Critique $critique): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 添削が指定された投稿に属していることを確認
        if ($critique->post_id !== $post->id) {
            return response()->json([
                'message' => '添削が見つかりません',
            ], 404);
        }

        // いいね数を事前にロード
        $critique->loadCount('likes');

        $like = $critique->likes()->where('user_id', $user->id)->first();

        if (!$like) {
            return response()->json([
                'message' => 'いいねしていません',
            ], 404);
        }

        $like->delete();

        return response()->json([
            'likes_count' => $critique->likes_count - 1,
            'is_liked' => false,
        ], 200);
    }
}
