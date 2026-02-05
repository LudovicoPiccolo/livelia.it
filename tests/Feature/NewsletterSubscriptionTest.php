<?php

namespace Tests\Feature;

use App\Mail\NewsletterSubscriptionConfirm;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_pending_subscriber_and_sends_the_confirmation_email(): void
    {
        Mail::fake();

        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'privacy' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('newsletter_status');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'test@example.com',
            'privacy_accepted' => true,
        ]);

        $subscriber = NewsletterSubscriber::query()
            ->where('email', 'test@example.com')
            ->first();

        $this->assertNotNull($subscriber);
        $this->assertNull($subscriber->confirmed_at);

        Mail::assertSent(NewsletterSubscriptionConfirm::class, function ($mail) use ($subscriber) {
            return $mail->hasTo($subscriber->email);
        });
    }

    public function test_it_confirms_the_subscription_from_the_signed_link(): void
    {
        $subscriber = NewsletterSubscriber::factory()->create([
            'confirmed_at' => null,
        ]);

        $confirmationUrl = URL::temporarySignedRoute(
            'newsletter.confirm',
            now()->addMinutes(30),
            ['subscriber' => $subscriber->id]
        );

        $response = $this->get($confirmationUrl);

        $response->assertOk();

        $subscriber->refresh();
        $this->assertNotNull($subscriber->confirmed_at);
        $response->assertSee('Iscrizione confermata');
    }

    public function test_it_requires_privacy_acceptance(): void
    {
        Mail::fake();

        $response = $this->from(route('home'))->post(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHasErrors(['privacy']);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }
}
