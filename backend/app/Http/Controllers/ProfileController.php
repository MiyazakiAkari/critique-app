<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * 指定ユーザーのプロフィールを取得
     */
    public function show($username)
    {
        $user = User::where('username', $username)->firstOrFail();
        $profile = $user->profile;

        // プロフィールが存在しない場合は作成
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'bio' => '',
                'avatar_url' => null,
            ]);
        }

        return response()->json([
            'user' => $user->only(['id', 'name', 'username', 'email', 'created_at']),
            'profile' => $profile->only(['id', 'bio', 'avatar_url', 'updated_at']),
        ]);
    }

    /**
     * ログインユーザーのプロフィールを取得
     */
    public function me()
    {
        $user = Auth::user();
        $profile = $user->profile;

        // プロフィールが存在しない場合は作成
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'bio' => '',
                'avatar_url' => null,
            ]);
        }

        return response()->json([
            'user' => $user->only(['id', 'name', 'username', 'email', 'created_at']),
            'profile' => $profile->only(['id', 'bio', 'avatar_url', 'updated_at']),
        ]);
    }

    /**
     * プロフィールを更新
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bio' => 'nullable|string|max:500',
            'avatar_url' => 'nullable|string|url',
        ]);

        $profile = $user->profile;

        // プロフィールが存在しない場合は作成
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'bio' => $validated['bio'] ?? '',
                'avatar_url' => $validated['avatar_url'] ?? null,
            ]);
        } else {
            $profile->update($validated);
        }

        return response()->json([
            'message' => 'プロフィールを更新しました',
            'profile' => $profile->only(['id', 'bio', 'avatar_url', 'updated_at']),
        ]);
    }

    /**
     * アバター画像をアップロード
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();
        $profile = $user->profile;

        // プロフィールが存在しない場合は作成
        if (!$profile) {
            $profile = Profile::create([
                'user_id' => $user->id,
                'bio' => '',
                'avatar_url' => null,
            ]);
        }

        // 古い画像を削除
        if ($profile->avatar_url) {
            $oldPath = str_replace('/storage/', '', $profile->avatar_url);
            Storage::disk('public')->delete($oldPath);
        }

        // 新しい画像を保存
        $path = $request->file('avatar')->store('avatars', 'public');
        $url = Storage::url($path);

        $profile->update(['avatar_url' => $url]);

        return response()->json([
            'message' => 'アバターをアップロードしました',
            'avatar_url' => $url,
        ]);
    }

    /**
     * プロフィールを削除（リセット）
     */
    public function destroy()
    {
        $user = Auth::user();
        $profile = $user->profile;

        if ($profile) {
            // アバター画像を削除
            if ($profile->avatar_url) {
                $oldPath = str_replace('/storage/', '', $profile->avatar_url);
                Storage::disk('public')->delete($oldPath);
            }

            // プロフィールをリセット
            $profile->update([
                'bio' => '',
                'avatar_url' => null,
            ]);
        }

        return response()->json([
            'message' => 'プロフィールをリセットしました',
        ]);
    }
}
