<?php

namespace App\Http\Controllers;

use App\Models\AiPost;
use App\Models\ChatMessage;
use App\Models\UserReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserReactionController extends Controller
{
    public function togglePost(Request $request, AiPost $post): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $existing = UserReaction::query()
            ->where('user_id', $user->id)
            ->where('target_type', 'post')
            ->where('target_id', $post->id)
            ->where('reaction_type', 'like')
            ->first();

        $liked = ! $existing;

        if ($existing) {
            $existing->delete();
        } else {
            UserReaction::create([
                'user_id' => $user->id,
                'target_type' => 'post',
                'target_id' => $post->id,
                'reaction_type' => 'like',
            ]);
        }

        if ($request->expectsJson()) {
            $humanLikesCount = $post->humanLikes()->count();
            $aiLikesCount = $post->reactions()->where('reaction_type', 'like')->count();

            return response()->json([
                'liked' => $liked,
                'human_likes_count' => $humanLikesCount,
                'ai_likes_count' => $aiLikesCount,
            ]);
        }

        return back()->with('status', $liked ? 'Mi piace aggiunto.' : 'Mi piace rimosso.');
    }

    public function toggleChat(Request $request, ChatMessage $message): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $existing = UserReaction::query()
            ->where('user_id', $user->id)
            ->where('target_type', 'chat')
            ->where('target_id', $message->id)
            ->where('reaction_type', 'like')
            ->first();

        $liked = ! $existing;

        if ($existing) {
            $existing->delete();
        } else {
            UserReaction::create([
                'user_id' => $user->id,
                'target_type' => 'chat',
                'target_id' => $message->id,
                'reaction_type' => 'like',
            ]);
        }

        if ($request->expectsJson()) {
            $humanLikesCount = $message->humanLikes()->count();

            return response()->json([
                'liked' => $liked,
                'human_likes_count' => $humanLikesCount,
            ]);
        }

        return back()->with('status', $liked ? 'Mi piace aggiunto.' : 'Mi piace rimosso.');
    }
}
