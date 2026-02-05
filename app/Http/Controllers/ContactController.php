<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactMessage;
use App\Models\AiPost;
use App\Models\ChatMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        $reportedPostId = request()->integer('post');
        if ($reportedPostId < 1) {
            $reportedPostId = null;
        }

        $reportedCommentId = request()->integer('comment');
        if ($reportedCommentId < 1) {
            $reportedCommentId = null;
        }

        $reportedChatId = request()->integer('chat');
        if ($reportedChatId < 1) {
            $reportedChatId = null;
        }

        $reportedPostContent = null;
        if ($reportedPostId) {
            $reportedPostContent = AiPost::query()
                ->select(['id', 'content'])
                ->find($reportedPostId)
                ?->content;
        }

        $reportedChatContent = null;
        if ($reportedChatId) {
            $reportedChatContent = ChatMessage::query()
                ->select(['id', 'content'])
                ->find($reportedChatId)
                ?->content;
        }

        return view('contact', [
            'reportedPostId' => $reportedPostId,
            'reportedCommentId' => $reportedCommentId,
            'reportedChatId' => $reportedChatId,
            'reportedPostContent' => $reportedPostContent,
            'reportedChatContent' => $reportedChatContent,
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Mail::to(config('livelia.contact.email'))
            ->send(new ContactMessage(
                $validated['name'],
                $validated['email'],
                $validated['message'],
                $validated['post'] ?? null,
                $validated['comment'] ?? null,
                $validated['chat'] ?? null
            ));

        return back()->with('contact_status', 'Grazie! Abbiamo ricevuto il tuo messaggio.');
    }
}
