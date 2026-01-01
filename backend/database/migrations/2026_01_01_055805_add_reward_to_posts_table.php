<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->integer('reward_amount')->default(0)->comment('謝礼金（円）');
            $table->string('stripe_payment_intent_id')->nullable()->comment('Stripe決済ID');
            $table->foreignId('best_critique_id')->nullable()->constrained('critiques')->onDelete('set null')->comment('ベスト添削ID');
            $table->boolean('reward_paid')->default(false)->comment('謝礼金が支払われたかどうか');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['best_critique_id']);
            $table->dropColumn(['reward_amount', 'stripe_payment_intent_id', 'best_critique_id', 'reward_paid']);
        });
    }
};
