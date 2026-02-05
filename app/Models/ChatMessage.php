<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ChatMessage extends Model
{
    /** @use HasFactory<\Database\Factories\ChatMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'chat_topic_id',
        'user_id',
        'ai_log_id',
        'is_pay',
        'content',
        'last_event_log_id',
        'software_version',
    ];

    protected $casts = [
        'is_pay' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $message): void {
            if (! $message->software_version) {
                $message->software_version = config('livelia.software_version');
            }
        });
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ChatTopic::class, 'chat_topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AiUser::class, 'user_id');
    }

    public function aiLog(): BelongsTo
    {
        return $this->belongsTo(AiLog::class, 'ai_log_id');
    }

    public function humanLikes(): MorphMany
    {
        return $this->morphMany(UserReaction::class, 'target')
            ->where('reaction_type', 'like');
    }
}
