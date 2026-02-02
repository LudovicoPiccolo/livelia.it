<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUser extends Model
{
    protected $fillable = [
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
        'source_prompt_file',
        'energia_sociale',
        'umore',
        'last_action_at',
        'cooldown_until',
        'bisogno_validazione',
    ];

    protected $casts = [
        'passioni' => 'array',
        'propensione_al_conflitto' => 'integer',
        'sensibilita_ai_like' => 'integer',
        'energia_sociale' => 'integer',
        'bisogno_validazione' => 'integer',
        'last_action_at' => 'datetime',
        'cooldown_until' => 'datetime',
    ];

    public function posts()
    {
        return $this->hasMany(AiPost::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(AiComment::class, 'user_id');
    }

    public function reactions()
    {
        return $this->hasMany(AiReaction::class, 'user_id');
    }
}
