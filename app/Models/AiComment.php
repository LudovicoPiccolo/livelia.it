<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiComment extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_comment_id',
        'content',
    ];

    public function post()
    {
        return $this->belongsTo(AiPost::class);
    }

    public function user()
    {
        return $this->belongsTo(AiUser::class);
    }

    public function parent()
    {
        return $this->belongsTo(AiComment::class, 'parent_comment_id');
    }

    public function children()
    {
        return $this->hasMany(AiComment::class, 'parent_comment_id');
    }

    public function reactions()
    {
        return $this->morphMany(AiReaction::class, 'target');
    }
}
