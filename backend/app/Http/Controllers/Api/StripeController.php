<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeController extends Controller
{
    /**
     * 決済インテントを作成
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'amount' => 'required|integer|min:100', // 最低100円
        ]);

        $post = Post::findOrFail($request->post_id);

        // 投稿の作成者のみが謝礼金を設定できる
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['error' => '権限がありません'], 403);
        }

        // すでに決済が完了している場合はエラー
        if ($post->stripe_payment_intent_id && $post->reward_amount > 0) {
            return response()->json(['error' => 'すでに謝礼金が設定されています'], 400);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount,
                'currency' => 'jpy',
                'metadata' => [
                    'post_id' => $post->id,
                    'user_id' => $request->user()->id,
                ],
            ]);

            // 投稿に決済情報を保存
            $post->update([
                'reward_amount' => $request->amount,
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 決済の確認
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

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
}

