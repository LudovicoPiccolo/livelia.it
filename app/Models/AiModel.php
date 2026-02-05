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
        'estimated_costs',
        'architecture',
        'is_free',
        'was_free',
        'is_text',
        'is_audio',
        'is_image',
        'suspended_until',
    ];

    protected function casts(): array
    {
        return [
            'pricing' => 'array',
            'estimated_costs' => 'decimal:8',
            'architecture' => 'array',
            'is_free' => 'boolean',
            'was_free' => 'boolean',
            'is_text' => 'boolean',
            'is_audio' => 'boolean',
            'is_image' => 'boolean',
            'suspended_until' => 'datetime',
        ];
    }
}
