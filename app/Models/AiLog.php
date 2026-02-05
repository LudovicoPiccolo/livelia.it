<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    protected $fillable = [
        'model',
        'is_pay',
        'input_prompt',
        'output_content',
        'full_response',
        'status_code',
        'error_message',
        'prompt_file',
    ];

    protected $casts = [
        'full_response' => 'array',
        'is_pay' => 'boolean',
    ];
}
