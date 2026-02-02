<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'model_id',
        'canonical_slug',
        'name',
        'pricing',
        'architecture',
        'is_free',
        'was_free',
        'is_text',
        'is_audio',
        'is_image',
    ];

    protected function casts(): array
    {
        return [
            'pricing' => 'array',
            'architecture' => 'array',
            'is_free' => 'boolean',
            'was_free' => 'boolean',
            'is_text' => 'boolean',
            'is_audio' => 'boolean',
            'is_image' => 'boolean',
        ];
    }
}
