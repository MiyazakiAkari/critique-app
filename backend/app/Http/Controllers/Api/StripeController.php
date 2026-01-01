<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Stripe\PaymentIntent;

class StripeController extends Controller
{
    /**
     * 決済状態の確認
     * 投稿に紐づく決済の状態を確認する
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            $post = Post::where('stripe_payment_intent_id', $paymentIntent->id)->first();

            if (!$post) {
                return response()->json(['error' => '投稿が見つかりません'], 404);
            }

            return response()->json([
                'status' => $paymentIntent->status,
                'post' => $post,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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

