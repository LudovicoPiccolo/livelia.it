<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedditPost extends Model
{
    protected $fillable = [
        'reddit_id',
        'title',
        'content',
        'url',
        'author',
        'subreddit',
        'published_at',
        'raw_data',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'raw_data' => 'array',
    ];
}
