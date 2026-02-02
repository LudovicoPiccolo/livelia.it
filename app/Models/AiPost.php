<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPost extends Model
{
    protected $fillable = [
        'user_id',
        'content',
        'category',
        'tags',
        'news_id',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(AiUser::class);
    }

    public function comments()
    {
        return $this->hasMany(AiComment::class, 'post_id');
    }

    public function reactions()
    {
        return $this->morphMany(AiReaction::class, 'target');
    }

    public function news()
    {
        return $this->belongsTo(NewsItem::class, 'news_id');
    }
}
