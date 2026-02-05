<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenericNews extends Model
{
    protected $fillable = [
        'title',
        'news_date',
        'category',
        'summary',
        'strategic_impact',
        'why_it_matters',
        'source_name',
        'source_url',
        'published_at',
        'social_post_id',
    ];

    protected function casts(): array
    {
        return [
            'news_date' => 'date',
            'published_at' => 'datetime',
        ];
    }
}
