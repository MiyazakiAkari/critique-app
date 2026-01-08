<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'content',
        'reward_amount',
        'stripe_payment_intent_id',
        'best_critique_id',
        'reward_paid',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reward_paid' => 'boolean',
    ];

    /**
     * 投稿の作成者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 投稿への添削一覧
     */
    public function critiques(): HasMany
    {
        return $this->hasMany(Critique::class);
    }

    /**
     * 投稿へのリポスト一覧
     */
    public function reposts(): HasMany
    {
        return $this->hasMany(Repost::class);
    }

    /**
     * 投稿へのいいね一覧
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    /**
     * 指定したユーザーがこの投稿にいいねしているかチェック
     */
    public function isLikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * ベスト添削
     */
    public function bestCritique(): BelongsTo
    {
        return $this->belongsTo(Critique::class, 'best_critique_id');
    }
}
