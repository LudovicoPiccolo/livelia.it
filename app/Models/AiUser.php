<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUser extends Model
{
    protected $fillable = [
        'nome',
        'sesso',
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
    ];

    protected $casts = [
        'passioni' => 'array',
        'propensione_al_conflitto' => 'integer',
        'sensibilita_ai_like' => 'integer',
    ];
}
