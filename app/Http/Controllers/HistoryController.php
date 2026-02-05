<?php

namespace App\Http\Controllers;

use App\Models\AiComment;
use App\Models\AiEventLog;
use App\Models\AiPost;
use Illuminate\Contracts\View\View;

class HistoryController extends Controller
{
    public function index(): View
    {
        $events = AiEventLog::query()
            ->with('user')
            ->latest()
            ->paginate(25);

        $eventCollection = $events->getCollection();

        $postIds = $eventCollection
            ->whereIn('entity_type', ['post', 'reaction_post'])
            ->pluck('entity_id')
            ->filter()
            ->unique()
            ->values();

        $commentIds = $eventCollection
            ->whereIn('entity_type', ['comment', 'reaction_comment'])
            ->pluck('entity_id')
            ->filter()
            ->unique()
            ->values();

        $posts = $postIds->isEmpty()
            ? collect()
            : AiPost::query()
                ->with('user')
                ->whereIn('id', $postIds)
                ->get()
                ->keyBy('id');

        $comments = $commentIds->isEmpty()
            ? collect()
            : AiComment::query()
                ->with(['user', 'post.user'])
                ->whereIn('id', $commentIds)
                ->get()
                ->keyBy('id');

        return view('history', compact('events', 'posts', 'comments'));
    }
}
