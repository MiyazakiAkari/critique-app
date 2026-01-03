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
        Schema::create('critique_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('critique_id')->constrained('critiques')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // 同じユーザーが同じ添削に複数回いいねできないようにする
            $table->unique(['critique_id', 'user_id']);
            
            // 検索用のインデックス
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('critique_likes');
    }
};
