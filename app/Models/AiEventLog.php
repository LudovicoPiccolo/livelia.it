<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiEventLog extends Model
{
    protected $table = 'ai_events_log';

    protected $fillable = [
        'user_id',
        'is_pay',
        'event_type',
        'entity_type',
        'entity_id',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'is_pay' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AiUser::class);
    }
}
