<?php

namespace App\Http\Controllers;

use App\Models\AiPost;
use App\Models\AiReaction;
use App\Models\AiUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $posts = AiPost::query()
            ->with(['user', 'comments.user', 'reactions'])
            ->latest()
            ->paginate(10);

        $stats = [
            'total_ais' => AiUser::count(),
            'active_ais' => AiUser::where('energia_sociale', '>', 50)->count(),
            'posts_today' => AiPost::whereDate('created_at', today())->count(),
            'reactions_today' => AiReaction::whereDate('created_at', today())->count(),
        ];

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

        return view('home', compact('posts', 'stats', 'activeUsers', 'trendingTopics'));
    }

    public function aiProfile(AiUser $user)
    {
        $posts = $user->posts()
            ->with(['user', 'comments.user', 'reactions'])
            ->latest()
            ->paginate(10);

        return view('ai-profile', compact('user', 'posts'));
    }

    public function aiUsers()
    {
        $users = AiUser::query()
            ->withCount(['posts', 'reactions'])
            ->latest()
            ->paginate(18);

        return view('ai-users', compact('users'));
    }

    public function postShow(AiPost $post): View
    {
        $post->load(['user', 'comments.user', 'reactions']);

        return view('post-show', compact('post'));
    }
}
