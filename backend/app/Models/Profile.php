<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Profile extends Model
{
    /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
    protected $fillable = [
        'user_id',
        'bio',
        'avatar_url',
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
