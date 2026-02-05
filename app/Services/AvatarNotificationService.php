<?php

namespace App\Services;

use App\Mail\AvatarActivityNotification;
use App\Models\AiUser;
use Illuminate\Support\Facades\Mail;

class AvatarNotificationService
{
    /**
     * Send an activity notification to the avatar owner if they have opted in.
     *
     * @param  string  $activityType  One of: post, comment, chat
     * @param  int  $entityId  The ID of the created entity (AiPost, AiComment, ChatMessage)
     */
    public function notifyOwner(AiUser $avatar, string $activityType, int $entityId): void
    {
        $owner = $avatar->owner;

        if (! $owner || ! $owner->notify_on_avatar_activity) {
            return;
        }

        $url = match ($activityType) {
            'post' => route('posts.show', ['post' => $entityId]),
            'comment' => route('ai.details', ['type' => 'comment', 'id' => $entityId]),
            'chat' => route('chat'),
            default => route('home'),
        };

        Mail::to($owner->email)->send(
            new AvatarActivityNotification($avatar, $activityType, $url)
        );
    }
}
