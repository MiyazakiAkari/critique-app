<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;

class StripeController extends Controller
{
    /**
     * 決済状態の確認
     * 投稿に紐づく決済の状態を確認する（認証ユーザーの投稿のみ）
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $user = $request->user();

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            // PaymentIntentのメタデータでユーザーIDを検証
            $intentUserId = $paymentIntent->metadata->user_id ?? null;
            if ($intentUserId !== null && (string) $intentUserId !== (string) $user->id) {
                Log::warning('Unauthorized payment intent access attempt', [
                    'user_id' => $user->id,
                    'payment_intent_id' => $request->payment_intent_id,
                    'intent_user_id' => $intentUserId,
                ]);
                return response()->json(['error' => '決済情報へのアクセス権限がありません'], 403);
            }

            // 投稿を取得し、所有者を検証
            $post = Post::where('stripe_payment_intent_id', $paymentIntent->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$post) {
                return response()->json(['error' => '投稿が見つかりません'], 404);
            }

            // 必要最小限の情報のみ返す（フルモデルは返さない）
            return response()->json([
                'status' => $paymentIntent->status,
                'post_id' => $post->id,
                'reward_amount' => $post->reward_amount,
                'reward_paid' => $post->reward_paid,
            ]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // 無効なPaymentIntent IDの場合
            return response()->json(['error' => '決済情報が見つかりません'], 404);
        } catch (\Exception $e) {
            Log::error('Payment confirmation error', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);
            return response()->json(['error' => '決済情報の確認中にエラーが発生しました'], 500);
        }
    }

    /**
     * 決済履歴の取得
     * 認証ユーザーの決済履歴を取得する
     */
    public function getPaymentHistory(Request $request)
    {
        $user = $request->user();
        
        $posts = Post::where('user_id', $user->id)
            ->whereNotNull('stripe_payment_intent_id')
            ->where('reward_amount', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'content', 'reward_amount', 'stripe_payment_intent_id', 'created_at']);

        return response()->json([
            'payments' => $posts,
        ]);
    }
}

