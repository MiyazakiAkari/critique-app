<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Critique;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Transfer;

class BestCritiqueController extends Controller
{
    /**
     * ベスト添削を選択して謝礼金を支払う
     */
    public function selectBestCritique(Request $request, Post $post)
    {
        $request->validate([
            'critique_id' => 'required|exists:critiques,id',
        ]);

        // 投稿の作成者のみがベスト添削を選択できる
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['error' => '権限がありません'], 403);
        }

        $critique = Critique::findOrFail($request->critique_id);

        // 指定された添削がこの投稿に対するものか確認
        if ($critique->post_id !== $post->id) {
            return response()->json(['error' => '無効な添削です'], 400);
        }

        // 自分の添削を選択することはできない
        if ($critique->user_id === $request->user()->id) {
            return response()->json(['error' => '自分の添削は選択できません'], 400);
        }

        // すでにベスト添削が選択されている場合はエラー
        if ($post->best_critique_id) {
            return response()->json(['error' => 'すでにベスト添削が選択されています'], 400);
        }

        // 謝礼金が設定されていない場合
        if ($post->reward_amount <= 0) {
            return response()->json(['error' => '謝礼金が設定されていません'], 400);
        }

        try {
            DB::beginTransaction();

            // ベスト添削を設定
            $post->update([
                'best_critique_id' => $critique->id,
                'reward_paid' => true,
            ]);

            // TODO: 実際のStripe Transferを実装する場合
            // Stripe Connectを使用して、添削者のアカウントに送金
            // この実装には添削者のStripe Connect アカウントIDが必要
            
            DB::commit();

            return response()->json([
                'message' => 'ベスト添削を選択しました',
                'post' => $post->load(['bestCritique.user']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

