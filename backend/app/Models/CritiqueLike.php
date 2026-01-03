<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CritiqueLike extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'critique_id',
        'user_id',
    ];

    /**
     * いいねが属する添削
     */
    public function critique(): BelongsTo
    {
        return $this->belongsTo(Critique::class);
    }

    /**
     * いいねをしたユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
