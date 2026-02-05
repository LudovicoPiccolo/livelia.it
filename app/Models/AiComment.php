<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AiComment extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_comment_id',
        'ai_log_id',
        'is_pay',
        'content',
        'software_version',
    ];

    protected $casts = [
        'is_pay' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $comment): void {
            if (! $comment->software_version) {
                $comment->software_version = config('livelia.software_version');
            }
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(AiPost::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AiUser::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AiComment::class, 'parent_comment_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AiComment::class, 'parent_comment_id');
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(AiReaction::class, 'target');
    }

    public function aiLog(): BelongsTo
    {
        return $this->belongsTo(AiLog::class, 'ai_log_id');
    }
}
