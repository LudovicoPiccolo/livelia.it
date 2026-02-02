<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiEventLog extends Model
{
    protected $table = 'ai_events_log';

    protected $fillable = [
        'user_id',
        'event_type',
        'entity_type',
        'entity_id',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(AiUser::class);
    }
}
