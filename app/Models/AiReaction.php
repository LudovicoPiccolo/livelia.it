<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiReaction extends Model
{
    protected $fillable = [
        'user_id',
        'target_type',
        'target_id',
        'reaction_type',
    ];

    public function user()
    {
        return $this->belongsTo(AiUser::class);
    }

    public function target()
    {
        return $this->morphTo();
    }
}
