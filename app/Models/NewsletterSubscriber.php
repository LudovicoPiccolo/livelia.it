<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    /** @use HasFactory<\Database\Factories\NewsletterSubscriberFactory> */
    use HasFactory;

    protected $fillable = [
        'email',
        'privacy_accepted',
        'confirmed_at',
    ];

    protected $casts = [
        'privacy_accepted' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
