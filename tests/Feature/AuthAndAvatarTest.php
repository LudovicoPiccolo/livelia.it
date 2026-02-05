<?php

namespace Tests\Feature;

use App\Mail\UserAvatarChanged;
use App\Mail\VerifyEmailNotification;
use App\Models\AiModel;
use App\Models\AiUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthAndAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_custom_verify_email(): void
    {
        Mail::fake();

        $this->post('/registrati', [
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com',
            'password' => 'Password1x',
            'password_confirmation' => 'Password1x',
        ]);

        Mail::assertSent(VerifyEmailNotification::class, function (VerifyEmailNotification $mail) {
            return in_array('mario@example.com', array_column($mail->to, 'address'));
        });
    }

    public function test_account_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('account'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_user_can_create_avatar_and_triggers_notification(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $model = AiModel::create([
            'model_id' => 'free-model-1',
            'is_free' => true,
            'is_text' => true,
        ]);

        $payload = $this->avatarPayload();

        $response = $this->actingAs($user)->post(route('account.avatar.store'), $payload);

        $response->assertRedirect(route('account'));

        $this->assertDatabaseHas('ai_users', [
            'user_id' => $user->id,
            'nome' => $payload['nome'],
            'generated_by_model' => $model->model_id,
            'is_pay' => false,
        ]);

        $avatar = AiUser::where('user_id', $user->id)->first();
        $this->assertNotNull($avatar?->avatar_updated_at);

        Mail::assertSent(UserAvatarChanged::class);
    }

    public function test_user_cannot_update_avatar_before_seven_days(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $avatar = AiUser::create(array_merge($this->avatarPayload(), [
            'user_id' => $user->id,
            'generated_by_model' => 'free-model-1',
            'source_prompt_file' => 'user_avatar_form',
            'is_pay' => false,
            'energia_sociale' => 100,
            'umore' => 'neutro',
            'bisogno_validazione' => 50,
            'avatar_updated_at' => now(),
        ]));

        $payload = $this->avatarPayload([
            'nome' => 'Nome aggiornato',
        ]);

        $response = $this->actingAs($user)->put(route('account.avatar.update'), $payload);

        $response->assertSessionHasErrors('avatar');
        $this->assertSame($avatar->nome, $avatar->refresh()->nome);
        Mail::assertNothingSent();
    }

    private function avatarPayload(array $overrides = []): array
    {
        return array_merge([
            'nome' => 'Avatar Test',
            'sesso' => 'femmina',
            'orientamento_sessuale' => 'eterosessuale',
            'lavoro' => 'Designer',
            'orientamento_politico' => 'moderato',
            'passioni' => 'arte, tecnologia',
            'bias_informativo' => 'Si affida a fonti internazionali e verifica sempre le notizie.',
            'personalita' => 'Curiosa, empatica e determinata a discutere con equilibrio.',
            'stile_comunicativo' => 'Diretto ma gentile, con esempi pratici.',
            'atteggiamento_verso_attualita' => 'Attenta agli impatti sociali e alle nuove tendenze.',
            'propensione_al_conflitto' => 40,
            'sensibilita_ai_like' => 60,
            'ritmo_attivita' => 'medio',
        ], $overrides);
    }
}
