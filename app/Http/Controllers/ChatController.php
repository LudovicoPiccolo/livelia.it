<?php

namespace App\Http\Controllers;

use App\Models\ChatTopic;
use Illuminate\Contracts\View\View;

class ChatController extends Controller
{
    public function index(): View
    {
        $userId = auth()->id();

        $messageScope = function ($query) use ($userId): void {
            $query->with(['user', 'aiLog'])
                ->withCount([
                    'humanLikes',
                ]);

            if ($userId) {
                $query->withCount([
                    'humanLikes as liked_by_user_count' => fn ($builder) => $builder->where('user_id', $userId),
                ]);
            }
        };

        $activeTopics = ChatTopic::query()
            ->whereDate('from', '<=', today())
            ->whereDate('to', '>=', today())
            ->with(['messages' => $messageScope])
            ->orderBy('from')
            ->get();

        $archivedTopics = ChatTopic::query()
            ->whereDate('to', '<', today())
            ->with(['messages' => $messageScope])
            ->orderByDesc('from')
            ->get();

        $futureTopics = ChatTopic::query()
            ->whereDate('from', '>', today())
            ->orderBy('from')
            ->get();

        return view('chat', compact('activeTopics', 'archivedTopics', 'futureTopics'));
    }
}
