<?php

namespace App\Http\Controllers;

use App\Models\AiPost;
use App\Models\AiUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $postsQuery = AiPost::query()
            ->with(['user', 'comments.user', 'comments.parent.user'])
            ->withCount([
                'comments',
                'humanLikes',
                'reactions as ai_likes_count' => fn ($query) => $query->where('reaction_type', 'like'),
            ]);

        if ($userId) {
            $postsQuery->withCount([
                'humanLikes as liked_by_user_count' => fn ($query) => $query->where('user_id', $userId),
            ]);
        }

        $posts = $postsQuery
            ->latest()
            ->paginate(10);

        $activeUsers = AiUser::query()
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->limit(5)
            ->get();

        $trendingTopics = AiPost::query()
            ->whereNotNull('category')
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($item) => ['name' => $item->category, 'count' => $item->count]);

        return view('home', compact('posts', 'activeUsers', 'trendingTopics'));
    }

    public function aiProfile(AiUser $user): View
    {
        $userId = auth()->id();

        $postsQuery = $user->posts()
            ->with(['user', 'comments.user', 'comments.parent.user'])
            ->withCount([
                'comments',
                'humanLikes',
                'reactions as ai_likes_count' => fn ($query) => $query->where('reaction_type', 'like'),
            ]);

        if ($userId) {
            $postsQuery->withCount([
                'humanLikes as liked_by_user_count' => fn ($query) => $query->where('user_id', $userId),
            ]);
        }

        $posts = $postsQuery
            ->latest()
            ->paginate(10);

        return view('ai-profile', compact('user', 'posts'));
    }

    public function aiUsers(): View
    {
        $users = AiUser::query()
            ->withCount(['posts', 'reactions'])
            ->latest()
            ->paginate(18);

        return view('ai-users', compact('users'));
    }

    public function postShow(AiPost $post): View
    {
        $userId = auth()->id();

        $post->load(['aiLog', 'news', 'user', 'comments.user', 'comments.parent.user']);
        $post->loadCount([
            'comments',
            'humanLikes',
            'reactions as ai_likes_count' => fn ($query) => $query->where('reaction_type', 'like'),
        ]);

        if ($userId) {
            $post->loadCount([
                'humanLikes as liked_by_user_count' => fn ($query) => $query->where('user_id', $userId),
            ]);
        }

        return view('post-show', compact('post'));
    }
}
