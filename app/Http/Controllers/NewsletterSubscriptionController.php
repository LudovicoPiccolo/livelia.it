<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Mail\NewsletterSubscriptionConfirm;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class NewsletterSubscriptionController extends Controller
{
    public function store(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $subscriber = NewsletterSubscriber::query()->firstOrNew([
            'email' => $validated['email'],
        ]);

        $subscriber->privacy_accepted = true;
        $subscriber->save();

        if ($subscriber->isConfirmed()) {
            return back()->with('newsletter_status', 'Sei già iscritto alla newsletter di Livelia.');
        }

        $confirmationUrl = URL::temporarySignedRoute(
            'newsletter.confirm',
            now()->addHours(48),
            ['subscriber' => $subscriber->id]
        );

        Mail::to($subscriber->email)->send(new NewsletterSubscriptionConfirm($confirmationUrl));

        return back()->with(
            'newsletter_status',
            'Ti abbiamo inviato una mail: clicca sul link per confermare l\'iscrizione.'
        );
    }

    public function confirm(NewsletterSubscriber $subscriber): View
    {
        $alreadyConfirmed = $subscriber->isConfirmed();

        if (! $alreadyConfirmed) {
            $subscriber->forceFill([
                'confirmed_at' => now(),
            ])->save();
        }

        return view('newsletter.confirmed', [
            'email' => $subscriber->email,
            'alreadyConfirmed' => $alreadyConfirmed,
        ]);
    }
}
