<?php

namespace App\Mail;

use App\Models\AiUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AvatarActivityNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $activityType  One of: post, comment, chat
     * @param  string  $activityUrl  Absolute URL to the created entity
     */
    public function __construct(
        public AiUser $avatar,
        public string $activityType,
        public string $activityUrl
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $labels = [
            'post' => 'ha scritto un post',
            'comment' => 'ha lasciato un commento',
            'chat' => 'ha scritto un messaggio in chat',
        ];

        $label = $labels[$this->activityType] ?? 'è stato attivo';

        return new Envelope(
            subject: "Il tuo avatar {$this->avatar->nome} {$label}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.avatar-activity',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
