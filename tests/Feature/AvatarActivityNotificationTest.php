<?php

namespace Tests\Feature;

use App\Mail\AvatarActivityNotification;
use App\Models\AiUser;
use App\Models\User;
use App\Services\AvatarNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AvatarActivityNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_enables_avatar_notifications(): void
    {
        $user = User::factory()->create(['notify_on_avatar_activity' => false]);
        AiUser::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('account.avatar.toggle-notify'))
            ->assertRedirect(route('account'));

        $this->assertTrue($user->refresh()->notify_on_avatar_activity);
    }

    public function test_toggle_disables_avatar_notifications(): void
    {
        $user = User::factory()->create(['notify_on_avatar_activity' => true]);
        AiUser::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('account.avatar.toggle-notify'))
            ->assertRedirect(route('account'));

        $this->assertFalse($user->refresh()->notify_on_avatar_activity);
    }

    public function test_notification_sent_when_owner_opted_in(): void
    {
        Mail::fake();

        $user = User::factory()->create(['notify_on_avatar_activity' => true]);
        $avatar = AiUser::factory()->create(['user_id' => $user->id]);

        $service = new AvatarNotificationService;
        $service->notifyOwner($avatar, 'post', 42);

        Mail::assertSent(AvatarActivityNotification::class, function (AvatarActivityNotification $mail) use ($user, $avatar) {
            return in_array($user->email, array_column($mail->to, 'address'))
                && $mail->avatar->id === $avatar->id
                && $mail->activityType === 'post';
        });
    }

    public function test_notification_not_sent_when_owner_opted_out(): void
    {
        Mail::fake();

        $user = User::factory()->create(['notify_on_avatar_activity' => false]);
        $avatar = AiUser::factory()->create(['user_id' => $user->id]);

        $service = new AvatarNotificationService;
        $service->notifyOwner($avatar, 'comment', 10);

        Mail::assertNothingSent();
    }

    public function test_notification_not_sent_when_avatar_has_no_owner(): void
    {
        Mail::fake();

        $avatar = AiUser::factory()->create(['user_id' => null]);

        $service = new AvatarNotificationService;
        $service->notifyOwner($avatar, 'chat', 5);

        Mail::assertNothingSent();
    }

    public function test_account_page_shows_toggle_on_when_enabled(): void
    {
        $user = User::factory()->create(['notify_on_avatar_activity' => true]);
        AiUser::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account'));

        $response->assertStatus(200);
        $response->assertSee('bg-emerald-500');
    }

    public function test_account_page_shows_toggle_off_when_disabled(): void
    {
        $user = User::factory()->create(['notify_on_avatar_activity' => false]);
        AiUser::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account'));

        $response->assertStatus(200);
        $response->assertSee('bg-neutral-300');
    }

    public function test_notification_email_subject_varies_by_activity_type(): void
    {
        Mail::fake();

        $user = User::factory()->create(['notify_on_avatar_activity' => true]);
        $avatar = AiUser::factory()->create(['user_id' => $user->id]);
        $service = new AvatarNotificationService;

        $service->notifyOwner($avatar, 'post', 1);
        $service->notifyOwner($avatar, 'comment', 2);
        $service->notifyOwner($avatar, 'chat', 3);

        Mail::assertSent(AvatarActivityNotification::class, 3);

        Mail::assertSent(AvatarActivityNotification::class, function (AvatarActivityNotification $mail) use ($avatar) {
            return $mail->activityType === 'post'
                && str_contains($mail->envelope()->subject, $avatar->nome)
                && str_contains($mail->envelope()->subject, 'ha scritto un post');
        });

        Mail::assertSent(AvatarActivityNotification::class, function (AvatarActivityNotification $mail) {
            return $mail->activityType === 'comment'
                && str_contains($mail->envelope()->subject, 'ha lasciato un commento');
        });

        Mail::assertSent(AvatarActivityNotification::class, function (AvatarActivityNotification $mail) {
            return $mail->activityType === 'chat'
                && str_contains($mail->envelope()->subject, 'ha scritto un messaggio in chat');
        });
    }
}
