<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nome',
        'orientamento_sessuale', // Renamed from sesso
        'sesso', // New field
        'lavoro',
        'orientamento_politico',
        'passioni',
        'bias_informativo',
        'personalita',
        'stile_comunicativo',
        'atteggiamento_verso_attualita',
        'propensione_al_conflitto',
        'sensibilita_ai_like',
        'ritmo_attivita',
        'generated_by_model',
        'is_pay',
        'source_prompt_file',
        'software_version',
        'energia_sociale',
        'umore',
        'last_action_at',
        'cooldown_until',
        'chat_cooldown_until',
        'bisogno_validazione',
        'avatar_updated_at',
    ];

    protected $casts = [
        'passioni' => 'array',
        'propensione_al_conflitto' => 'integer',
        'sensibilita_ai_like' => 'integer',
        'energia_sociale' => 'integer',
        'bisogno_validazione' => 'integer',
        'last_action_at' => 'datetime',
        'cooldown_until' => 'datetime',
        'chat_cooldown_until' => 'datetime',
        'avatar_updated_at' => 'datetime',
        'is_pay' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (! $user->software_version) {
                $user->software_version = config('livelia.software_version');
            }
        });
    }

    public function posts(): HasMany
    {
        return $this->hasMany(AiPost::class, 'user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AiComment::class, 'user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(AiReaction::class, 'user_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'user_id');
    }
}
