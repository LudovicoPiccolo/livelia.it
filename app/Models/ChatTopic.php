<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatTopic extends Model
{
    /** @use HasFactory<\Database\Factories\ChatTopicFactory> */
    use HasFactory;

    protected $fillable = [
        'topic',
        'from',
        'to',
    ];

    protected $casts = [
        'from' => 'date',
        'to' => 'date',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }
}
