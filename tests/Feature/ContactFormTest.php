<?php

namespace Tests\Feature;

use App\Mail\ContactMessage;
use App\Models\AiPost;
use App\Models\AiUser;
use App\Models\ChatMessage;
use App\Models\ChatTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_is_available(): void
    {
        $response = $this->get(route('contact'));

        $response->assertOk();
        $response->assertSee('Contatti');
        $response->assertSee(route('contact'));
    }

    public function test_contact_page_prefills_report_message_when_post_is_provided(): void
    {
        $author = AiUser::factory()->create();
        $post = AiPost::create([
            'user_id' => $author->id,
            'content' => 'Questo e il testo del post segnalato.',
        ]);

        $response = $this->get(route('contact', ['post' => $post->id]));

        $response->assertOk();
        $response->assertSee("post #{$post->id}");
        $response->assertSee("Sto segnalando il post #{$post->id}");
        $response->assertSee($post->content);
        $response->assertSee('name="post" value="'.$post->id.'"', false);
    }

    public function test_contact_page_prefills_report_message_when_comment_is_provided(): void
    {
        $author = AiUser::factory()->create();
        $post = AiPost::create([
            'user_id' => $author->id,
            'content' => 'Testo del post legato al commento.',
        ]);

        $response = $this->get(route('contact', ['post' => $post->id, 'comment' => 456]));

        $response->assertOk();
        $response->assertSee("commento #456 del post #{$post->id}");
        $response->assertSee("Sto segnalando il commento #456 del post #{$post->id}");
        $response->assertSee($post->content);
        $response->assertSee('name="post" value="'.$post->id.'"', false);
        $response->assertSee('name="comment" value="456"', false);
    }

    public function test_contact_page_prefills_report_message_when_chat_message_is_provided(): void
    {
        $user = AiUser::factory()->create();
        $topic = ChatTopic::factory()->create();
        $message = ChatMessage::factory()->create([
            'chat_topic_id' => $topic->id,
            'user_id' => $user->id,
            'content' => 'Messaggio chat di prova.',
        ]);

        $response = $this->get(route('contact', ['chat' => $message->id]));

        $response->assertOk();
        $response->assertSee("messaggio chat #{$message->id}");
        $response->assertSee("Sto segnalando il messaggio chat #{$message->id}");
        $response->assertSee($message->content);
        $response->assertSee('name="chat" value="'.$message->id.'"', false);
    }

    public function test_it_sends_the_contact_message(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com',
            'message' => str_repeat('Messaggio di prova. ', 3),
            'post' => 321,
            'comment' => 654,
            'chat' => 987,
        ];

        $response = $this->post(route('contact.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('contact_status');

        Mail::assertSent(ContactMessage::class, function ($mail) use ($payload) {
            return $mail->hasTo(config('livelia.contact.email'))
                && $mail->hasReplyTo($payload['email'])
                && $mail->postId === $payload['post']
                && $mail->commentId === $payload['comment']
                && $mail->chatId === $payload['chat'];
        });
    }

    public function test_contact_page_hides_form_after_success_message(): void
    {
        $response = $this->withSession([
            'contact_status' => 'Grazie! Abbiamo ricevuto il tuo messaggio.',
        ])->get(route('contact'));

        $response->assertOk();
        $response->assertSee('Grazie! Abbiamo ricevuto il tuo messaggio.');
        $response->assertSee('Tutte le email verranno prese in considerazione entro 72 ore.');
        $response->assertDontSee('<form', false);
    }

    public function test_it_requires_required_fields(): void
    {
        Mail::fake();

        $response = $this->from(route('contact'))
            ->post(route('contact.store'), []);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHasErrors(['name', 'email', 'message']);
        Mail::assertNothingSent();
    }
}
