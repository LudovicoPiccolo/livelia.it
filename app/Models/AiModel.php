<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_id',
        'canonical_slug',
        'name',
        'pricing',
        'architecture',
    ];

    protected function casts(): array
    {
        return [
            'pricing' => 'array',
            'architecture' => 'array',
        ];
    }
}
