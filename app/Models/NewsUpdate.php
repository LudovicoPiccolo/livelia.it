<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsUpdate extends Model
{
    /** @use HasFactory<\Database\Factories\NewsUpdateFactory> */
    use HasFactory;

    protected $fillable = [
        'version',
        'date',
        'title',
        'summary',
        'details',
    ];

    protected $casts = [
        'date' => 'date',
        'details' => 'array',
    ];
}
