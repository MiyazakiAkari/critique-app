<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * タイムライン取得（フォロー中のユーザーの投稿）
     */
    public function timeline(): JsonResponse
    {
         /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // フォロー中のユーザーIDを取得
        $followingIds = $user->followings()->pluck('users.id');
        
        // 自分の投稿も含める
        $followingIds->push($user->id);
        
        // フォロー中のユーザーの投稿を新しい順に取得
        $posts = Post::whereIn('user_id', $followingIds)
            ->with('user:id,name,username')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'image_path' => $post->image_path,
                    'image_url' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                    'created_at' => $post->created_at->toISOString(),
                    'user' => $post->user->only(['id', 'name', 'username']),
                ];
            });

        return response()->json([
            'posts' => $posts,
        ], 200);
    }

    /**
     * おすすめ投稿取得（全ユーザーの投稿）
     */
    public function recommended(): JsonResponse
    {
        $posts = Post::with('user:id,name,username')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'image_path' => $post->image_path,
                    'image_url' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                    'created_at' => $post->created_at->toISOString(),
                    'user' => $post->user->only(['id', 'name', 'username']),
                ];
            });

        return response()->json([
            'posts' => $posts,
        ], 200);
    }

    /**
     * 新規投稿作成
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:500',
             'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:10240', // 10MB
                function ($attribute, $value, $fail) {
                    if ($value && !@getimagesize($value->getRealPath())) {
                        $fail('The file must be a valid image.');
                    }
                },
            ],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // 画像を保存
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }
        
        $post = new Post();
        $post->user_id = $user->id;
        $post->content = $validated['content'];
        $post->image_path = $imagePath;
        $post->save();

        $post->load('user:id,name,username');

        return response()->json([
            'message' => '投稿しました',
            'post' => [
                'id' => $post->id,
                'content' => $post->content,
                'image_path' => $post->image_path,
                'image_url' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                'created_at' => $post->created_at->toISOString(),
                'user' => $post->user->only(['id', 'name', 'username']),
            ],
        ], 201);
    }

    /**
     * 投稿詳細取得
     */
    public function show(Post $post): JsonResponse
    {
        $post->load('user:id,name,username');

        return response()->json([
            'post' => [
                'id' => $post->id,
                'content' => $post->content,
                'image_path' => $post->image_path,
                'image_url' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                'created_at' => $post->created_at->toISOString(),
                'user' => $post->user->only(['id', 'name', 'username']),
            ],
        ], 200);
    }

    /**
     * 投稿削除
     */
    public function destroy(Post $post): JsonResponse
    {
         /** @var \App\Models\User $user */
        $user = Auth::user();

        // 自分の投稿のみ削除可能
        if ($post->user_id !== $user->id) {
            return response()->json([
                'message' => '削除権限がありません',
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => '投稿を削除しました',
        ], 200);
    }

    /**
     * 特定ユーザーの投稿一覧を取得
     */
    public function userPosts(string $username): JsonResponse
    {
        $user = \App\Models\User::where('username', $username)->firstOrFail();
        
        $posts = Post::where('user_id', $user->id)
            ->with('user:id,name,username')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'image_path' => $post->image_path,
                    'image_url' => $post->image_path ? asset('storage/' . $post->image_path) : null,
                    'created_at' => $post->created_at->toISOString(),
                    'user' => $post->user->only(['id', 'name', 'username']),
                ];
            });

        return response()->json([
            'posts' => $posts,
        ], 200);
    }
}
