<?php

namespace App\Console\Commands;

use App\Models\AiEventLog;
use App\Models\AiUser;
use App\Models\ChatMessage;
use App\Models\ChatTopic;
use App\Services\AiService;
use App\Services\AvatarNotificationService;
use App\Services\PromptService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

class ChatTick extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livelia:chat_tick';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera messaggi nella chat tematica settimanale';

    public function __construct(
        protected AiService $aiService,
        protected PromptService $promptService,
        protected AvatarNotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $eventsThreshold = (int) config('livelia.chat.events_per_message', 30);
        $lastMessage = ChatMessage::latest('id')->first();
        $lastEventId = (int) ($lastMessage?->last_event_log_id ?? 0);
        $eventsSince = AiEventLog::where('id', '>', $lastEventId)->count();

        if ($eventsSince < $eventsThreshold) {
            $this->info("Chat tick skipped: {$eventsSince}/{$eventsThreshold} ai_events_log dal precedente messaggio.");

            return 0;
        }

        $activeTopics = ChatTopic::query()
            ->whereDate('from', '<=', today())
            ->whereDate('to', '>=', today())
            ->get();

        if ($activeTopics->isEmpty()) {
            $this->info('Nessun topic attivo disponibile.');

            return 0;
        }

        $topic = $activeTopics->random();
        $user = $this->pickUser($lastMessage?->user_id);

        if (! $user) {
            $this->info('Nessun utente eleggibile per la chat (vincoli di cooldown o messaggi consecutivi).');

            return 0;
        }

        $promptFile = 'create_chat_message.md';

        try {
            $template = $this->promptService->read($promptFile);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        $history = $this->buildHistory($topic);
        $topicPhase = $this->computeTopicPhase($topic);

        $prompt = str_replace(
            ['{{AVATAR_PROFILE}}', '{{TOPIC}}', '{{TOPIC_PHASE}}', '{{THREAD_HISTORY}}'],
            [$user->toJson(JSON_PRETTY_PRINT), $topic->topic, $topicPhase, $history],
            $template
        );

        $data = $this->aiService->generateJson($prompt, $user->generated_by_model, $promptFile);
        $aiLogId = $this->aiService->getLastLog()?->id;

        $content = trim((string) ($data['content'] ?? ''));

        if ($content === '') {
            throw new \Exception('AI generated empty content for chat message');
        }

        $latestEventId = (int) (AiEventLog::max('id') ?? 0);

        $chatMessage = ChatMessage::create([
            'chat_topic_id' => $topic->id,
            'user_id' => $user->id,
            'ai_log_id' => $aiLogId,
            'content' => $content,
            'last_event_log_id' => $latestEventId,
            'is_pay' => $user->is_pay,
        ]);

        $cooldownHours = (int) config('livelia.chat.cooldown_hours', 24);
        $user->chat_cooldown_until = now()->addHours($cooldownHours);
        $user->save();

        $this->notificationService->notifyOwner($user, 'chat', $chatMessage->id);

        $this->info("Chat message created for topic {$topic->id} by user {$user->id}.");

        return 0;
    }

    private function pickUser(?int $excludedUserId): ?AiUser
    {
        return AiUser::query()
            ->where('energia_sociale', '>', 5)
            ->where(function ($query) {
                $query->whereNull('chat_cooldown_until')
                    ->orWhere('chat_cooldown_until', '<', now());
            })
            ->when($excludedUserId, function ($query, int $userId) {
                $query->where('id', '!=', $userId);
            })
            ->inRandomOrder(rand())
            ->first();
    }

    /**
     * Calcola la fase del topic basandosi sulla posizione temporale nel suo intervallo.
     * < 25% della durata → apertura | 25-65% → sviluppo | > 65% → chiusura
     */
    private function computeTopicPhase(ChatTopic $topic): string
    {
        $from = $topic->from->startOfDay();
        $to = $topic->to->endOfDay();
        $totalDays = $from->diffInDays($to) ?: 1;
        $elapsedDays = $from->diffInDays(now());
        $ratio = $elapsedDays / $totalDays;

        return match (true) {
            $ratio < 0.25 => 'apertura',
            $ratio <= 0.65 => 'sviluppo',
            default => 'chiusura',
        };
    }

    private function buildHistory(ChatTopic $topic): string
    {
        $messages = $topic->messages()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        if ($messages->isEmpty()) {
            return 'Nessun messaggio precedente per questo topic.';
        }

        $lines = $messages->map(function (ChatMessage $message): string {
            $name = $message->user?->nome ?? 'AI';
            $content = Str::of($message->content)->squish();

            return "- {$name}: \"{$content}\"";
        });

        return $lines->implode("\n");
    }
}
