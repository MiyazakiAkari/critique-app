<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    /**
     * 指定したユーザーをフォローする
     */
    /** @var \App\Models\User $user */
    public function follow(Request $request, string $username): JsonResponse
    {
        $user = Auth::user();
        $targetUser = User::where('username', $username)->firstOrFail();

        // 自分自身をフォローできない
        if ($user->id === $targetUser->id) {
            return response()->json([
                'message' => '自分自身をフォローすることはできません',
            ], 400);
        }

        // 既にフォローしている場合
        if ($user->isFollowing($targetUser)) {
            return response()->json([
                'message' => '既にフォローしています',
            ], 400);
        }

        $user->followings()->attach($targetUser->id);

        return response()->json([
            'message' => 'フォローしました',
            'is_following' => true,
        ], 200);
    }

    /**
     * 指定したユーザーのフォローを解除する
     */
    /** @var \App\Models\User $user */
    public function unfollow(Request $request, string $username): JsonResponse
    {
        $user = Auth::user();
        $targetUser = User::where('username', $username)->firstOrFail();

        // フォローしていない場合
        if (!$user->isFollowing($targetUser)) {
            return response()->json([
                'message' => 'フォローしていません',
            ], 400);
        }

        $user->followings()->detach($targetUser->id);

        return response()->json([
            'message' => 'フォローを解除しました',
            'is_following' => false,
        ], 200);
    }

    /**
     * 指定したユーザーのフォロワー一覧を取得
     */
    public function followers(string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();
        
        $followers = $user->followers()
            ->select('users.id', 'users.name', 'users.username')
            ->get();

        return response()->json([
            'followers' => $followers,
            'count' => $followers->count(),
        ], 200);
    }

    /**
     * 指定したユーザーがフォローしているユーザー一覧を取得
     */
    public function followings(string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();
        
        $followings = $user->followings()
            ->select('users.id', 'users.name', 'users.username')
            ->get();

        return response()->json([
            'followings' => $followings,
            'count' => $followings->count(),
        ], 200);
    }

    /**
     * 指定したユーザーとの関係性を取得（フォロー状態）
     */
    public function status(string $username): JsonResponse
    {
        $user = Auth::user();
        $targetUser = User::where('username', $username)->firstOrFail();

        $isFollowing = false;
        if ($user) {
            $isFollowing = $user->isFollowing($targetUser);
        }

        return response()->json([
            'is_following' => $isFollowing,
            'followers_count' => $targetUser->followers()->count(),
            'followings_count' => $targetUser->followings()->count(),
        ], 200);
    }
}
