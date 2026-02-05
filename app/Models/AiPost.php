<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AiPost extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'category',
        'tags',
        'news_id',
        'ai_log_id',
        'is_pay',
        'source_type',
        'software_version',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_pay' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $post): void {
            if (! $post->software_version) {
                $post->software_version = config('livelia.software_version');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AiUser::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AiComment::class, 'post_id');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(AiReaction::class, 'target');
    }

    public function humanLikes(): MorphMany
    {
        return $this->morphMany(UserReaction::class, 'target')
            ->where('reaction_type', 'like');
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(GenericNews::class, 'news_id');
    }

    public function aiLog(): BelongsTo
    {
        return $this->belongsTo(AiLog::class, 'ai_log_id');
    }
}
