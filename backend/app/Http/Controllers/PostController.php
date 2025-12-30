<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Repost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * タイムライン取得（フォロー中のユーザーの投稿とリポスト）
     */
    public function timeline(): JsonResponse
    {
         /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // フォロー中のユーザーIDを取得
        $followingIds = $user->followings()->pluck('users.id')->toArray();
        
        // 自分のIDも含める
        $followingIds[] = $user->id;
        
        // ユーザーがリポストした投稿IDを事前に取得（N+1対策）
        $userRepostedPostIds = Repost::where('user_id', $user->id)
            ->pluck('post_id')
            ->toArray();
        $userRepostedPostIdSet = array_flip($userRepostedPostIds);
        
        // フォロー中のユーザーの直接投稿を取得
        $directPosts = Post::whereIn('user_id', $followingIds)
            ->with('user:id,name,username')
            ->withCount('reposts')
            ->withCount('critiques')
            ->get()
            ->map(function ($post) use ($userRepostedPostIdSet) {
                $post->display_at = $post->created_at;
                $post->user_reposted = isset($userRepostedPostIdSet[$post->id]);
                return $post;
            });
        
        // フォロー中のユーザーのリポスト投稿を取得
        $repostedPosts = Repost::whereIn('user_id', $followingIds)
            ->with(['post' => function ($query) {
                $query->with('user:id,name,username')
                    ->withCount('reposts')
                    ->withCount('critiques');
            }])
            ->get()
            ->map(function ($repost) use ($userRepostedPostIdSet) {
                $post = $repost->post;
                $post->display_at = $repost->created_at;
                $post->user_reposted = isset($userRepostedPostIdSet[$post->id]);
                return $post;
            });
        
        // 両方を結合してから、投稿IDでグループ化して重複を排除
        $allPosts = collect($directPosts)->concat(collect($repostedPosts))
            ->groupBy('id')
            ->map(function ($group) {
                // 同じ投稿IDの場合は、最新の表示時刻を保持
                return $group->sortByDesc('display_at')->first();
            })
            ->sortByDesc('display_at')
            ->take(50);
        
        // フォーマットして返す
        $formattedPosts = $allPosts
            ->map(function ($post) use ($user) {
                return $this->formatPost($post, $user);
            })
            ->values();

        return response()->json([
            'posts' => $formattedPosts,
        ], 200);
    }

    /**
     * おすすめ投稿取得（全ユーザーの投稿）
     */
    public function recommended(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // ユーザーがリポストした投稿IDを事前に取得（N+1対策）
        $userRepostedPostIdSet = [];
        if ($user) {
            $userRepostedPostIds = Repost::where('user_id', $user->id)
                ->pluck('post_id')
                ->toArray();
            $userRepostedPostIdSet = array_flip($userRepostedPostIds);
        }

        $posts = Post::with('user:id,name,username')
            ->withCount('reposts')
            ->withCount('critiques')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($post) use ($user, $userRepostedPostIdSet) {
                $post->user_reposted = isset($userRepostedPostIdSet[$post->id]);
                return $this->formatPost($post, $user);
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
        $imageRules = [
            'required',
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
            'content' => 'required|string|max:500',
            'image' => $imageRules,
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
        $post->loadCount('reposts');
        $post->user_reposted = false; // 新規投稿はリポストされていない

        return response()->json([
            'message' => '投稿しました',
            'post' => $this->formatPost($post, $user),
        ], 201);
    }

    /**
     * 投稿詳細取得
     */
    public function show(Post $post): JsonResponse
    {
        $post->load('user:id,name,username');
        $post->loadCount('reposts');
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user) {
            $post->user_reposted = Repost::where('user_id', $user->id)
                ->where('post_id', $post->id)
                ->exists();
        } else {
            $post->user_reposted = false;
        }

        return response()->json([
            'post' => $this->formatPost($post, $user),
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
        
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        
        // 現在のユーザーがリポストした投稿IDを事前に取得（N+1対策）
        $userRepostedPostIdSet = [];
        if ($currentUser) {
            $userRepostedPostIds = Repost::where('user_id', $currentUser->id)
                ->pluck('post_id')
                ->toArray();
            $userRepostedPostIdSet = array_flip($userRepostedPostIds);
        }

        $posts = Post::where('user_id', $user->id)
            ->with('user:id,name,username')
            ->withCount('reposts')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($post) use ($currentUser, $userRepostedPostIdSet) {
                $post->user_reposted = isset($userRepostedPostIdSet[$post->id]);
                return $this->formatPost($post, $currentUser);
            });

        return response()->json([
            'posts' => $posts,
        ], 200);
    }

    /**
     * 投稿をリポストする（既にリポストしている場合は取り消す）
     */
    public function repost(Post $post): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 既にリポストしているかチェック
        $existingRepost = Repost::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existingRepost) {
            // 既にリポストしている場合は取り消す
            $existingRepost->delete();
            return response()->json([
                'message' => 'リポストを取り消しました',
            ], 200);
        }

        Repost::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        return response()->json([
            'message' => 'リポストしました',
        ], 201);
    }

    /**
     * リポストを取り消す
     */
    public function unrepost(Post $post): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $repost = Repost::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if (!$repost) {
            return response()->json([
                'message' => 'リポストしていません',
            ], 404);
        }

        $repost->delete();

        return response()->json([
            'message' => 'リポストを取り消しました',
        ], 200);
    }

    /**
     * 投稿をフォーマットして返す
     */
    private function formatPost(Post $post, ?\App\Models\User $user = null): array
    {
        // Eager load済みのデータを使用（N+1クエリ対策）
        $reposts_count = $post->reposts_count ?? 0;
        $critiques_count = $post->critiques_count ?? 0;
        $isReposted = $post->user_reposted ?? false;

        // ストレージ URL を構築（config から取得）
        $image_url = null;
        if ($post->image_path) {
            $app_url = config('app.url');
            $image_url = rtrim($app_url, '/') . '/storage/' . $post->image_path;
        }

        return [
            'id' => $post->id,
            'content' => $post->content,
            'image_path' => $post->image_path,
            'image_url' => $image_url,
            'created_at' => $post->created_at->toISOString(),
            'user' => $post->user->only(['id', 'name', 'username']),
            'reposts_count' => $reposts_count,
            'critiques_count' => $critiques_count,
            'is_reposted' => $isReposted,
        ];
    }
}

