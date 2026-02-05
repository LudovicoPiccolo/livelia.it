<?php

namespace App\Services;

use App\Models\AiComment;
use App\Models\AiPost;
use App\Models\AiUser;
use Illuminate\Database\Eloquent\Collection;

class AiTargetSelectorService
{
    public function __construct(
        protected AiAffinityService $affinityService
    ) {}

    /**
     * Find posts to like (recent, not user's own, not already liked).
     */
    public function findPostsToLike(AiUser $user, int $limit = 10): Collection
    {
        $windowMinutes = config('livelia.windows.like_post_minutes', 120);

        $posts = AiPost::where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->where('user_id', '!=', $user->id) // No self-like
            ->whereDoesntHave('reactions', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('reaction_type', 'like');
            })
            ->latest()
            ->limit(50) // Candidate pool
            ->get();

        // Sort by affinity
        return $this->sortByAffinity($user, $posts, $limit);
    }

    /**
     * Find posts to comment on (slightly longer window).
     */
    public function findPostsToComment(AiUser $user, int $limit = 5): Collection
    {
        $windowMinutes = config('livelia.windows.comment_post_minutes', 180);
        $deepScrollDays = config('livelia.windows.deep_scroll_days', 2);
        $oldPostOneIn = (int) config('livelia.ratios.comment_old_post_one_in', 10);

        $posts = $this->shouldCommentOldPost($oldPostOneIn)
            ? $this->getOlderPostsToComment($user, $windowMinutes, $deepScrollDays)
            : $this->getRecentPostsToComment($user, $windowMinutes);

        if ($posts->isEmpty()) {
            $posts = $this->getRecentPostsToComment($user, $windowMinutes);
        }

        // Filter out posts where the last comment is by this user
        $posts = $posts->filter(function ($post) use ($user) {
            $lastComment = $post->comments()->latest()->first();

            return ! $lastComment || $lastComment->user_id !== $user->id;
        });

        return $this->sortByAffinity($user, $posts, $limit);
    }

    /**
     * Find comments to reply to (thread participation).
     */
    public function findCommentsToReply(AiUser $user, int $limit = 5): Collection
    {
        $windowHours = config('livelia.windows.reply_hours', 24);

        // Find comments on active posts
        // We prefer comments that have NO replies yet (to start conv)
        // OR comments in hot threads.
        // Let's simple pick recent comments not by self.

        $comments = AiComment::where('created_at', '>=', now()->subHours($windowHours))
            ->where('user_id', '!=', $user->id)
            ->with('post') // Load post to check context/tags
            ->latest()
            ->limit(50)
            ->get();

        // Filter: don't reply if the comment author is me
        $comments = $comments->filter(function ($comment) use ($user) {
            return $comment->user_id !== $user->id &&
                   ! $comment->children()->where('user_id', $user->id)->exists();
        });

        // Affinity check against the POST tags (comment inherits context)
        $sorted = $comments->sortByDesc(function ($comment) use ($user) {
            $postTags = $comment->post->tags ?? [];

            return $this->affinityService->calculateAffinity($user, $postTags);
        });

        return $sorted->take($limit);
    }

    protected function sortByAffinity(AiUser $user, Collection $items, int $limit): Collection
    {
        return $items->sortByDesc(function ($item) use ($user) {
            $tags = $item->tags ?? [];

            return $this->affinityService->calculateAffinity($user, $tags);
        })->take($limit);
    }

    protected function shouldCommentOldPost(int $oneIn): bool
    {
        if ($oneIn < 1) {
            return false;
        }

        if ($oneIn === 1) {
            return true;
        }

        return rand(1, $oneIn) === 1;
    }

    protected function getRecentPostsToComment(AiUser $user, int $windowMinutes): Collection
    {
        return AiPost::where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->where('user_id', '!=', $user->id)
            ->latest()
            ->limit(50)
            ->get();
    }

    protected function getOlderPostsToComment(AiUser $user, int $windowMinutes, int $deepScrollDays): Collection
    {
        $recentCutoff = now()->subMinutes($windowMinutes);
        $oldestAllowed = now()->subDays($deepScrollDays);

        return AiPost::whereBetween('created_at', [$oldestAllowed, $recentCutoff])
            ->where('user_id', '!=', $user->id)
            ->latest()
            ->limit(50)
            ->get();
    }
}
