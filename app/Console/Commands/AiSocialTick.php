<?php

namespace App\Console\Commands;

use App\Models\AiComment;
use App\Models\AiEventLog;
use App\Models\AiPost;
use App\Models\AiReaction;
use App\Models\AiUser;
use App\Models\GenericNews;
use App\Services\AiActionDeciderService;
use App\Services\AiModelHealthService;
use App\Services\AiService;
use App\Services\AiTargetSelectorService;
use App\Services\AiUserStateService;
use App\Services\AvatarNotificationService;
use App\Services\PromptService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiSocialTick extends Command
{
    protected $signature = 'livelia:social_tick {--times=1 : Number of actions to run in this tick} {--ID= : Force a post using the specified GenericNews id}';

    protected $description = 'Execute one tick of social activity for AI users';

    public function __construct(
        protected AiService $aiService,
        protected AiUserStateService $stateService,
        protected AiActionDeciderService $deciderService,
        protected AiTargetSelectorService $targetService,
        protected AiModelHealthService $healthService,
        protected PromptService $promptService,
        protected AvatarNotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $times = max(1, (int) $this->option('times'));
        $forcedNewsId = $this->option('ID');
        $forcedNewsId = $forcedNewsId !== null ? (int) $forcedNewsId : null;
        if ($forcedNewsId !== null) {
            $times = 1;
        }

        for ($i = 0; $i < $times; $i++) {
            $didRun = $this->runSingleTick($forcedNewsId);
            if (! $didRun) {
                break;
            }
        }

        return 0;
    }

    private function runSingleTick(?int $forcedNewsId = null): bool
    {
        $this->info('Starting Social Tick...');

        // 0. Check Model Health
        $this->healthService->checkAndSuspendModels();

        // 1. Pick a User
        $user = $this->pickUser($forcedNewsId !== null);
        if (! $user) {
            $this->info('No eligible users found for this tick. Skipping.');

            return false;
        }

        $this->info("Selected User: {$user->nome} ({$user->id})");

        // 2. Decide Action
        $checkEvents = AiEventLog::where('user_id', $user->id)
            ->latest('id')
            ->take(20)
            ->get();

        $action = null;
        $forcedPost = false;
        if ($checkEvents->count() >= 20) {
            $allNull = $checkEvents->every(function ($event) {
                $status = $event->meta_json['status'] ?? null;

                return in_array($status, ['skipped', 'failed']);
            });

            if ($allNull) {
                $this->info("User {$user->id} has 20 consecutive null events. Forcing NEW_POST.");
                $action = 'NEW_POST';
                $forcedPost = true;
            }
        }

        if ($forcedNewsId !== null) {
            $this->info("Forced news ID provided: {$forcedNewsId}. Forcing NEW_POST.");
            $action = 'NEW_POST';
            $forcedPost = true;
        }

        if (! $forcedPost) {
            $commentThreshold = (int) config('livelia.ratios.comments_per_post', 10);
            if ($commentThreshold > 0) {
                $commentsSinceLastPost = $this->commentsSinceLastPost();
                if ($commentsSinceLastPost !== null && $commentsSinceLastPost >= $commentThreshold) {
                    $this->info("Comment threshold reached ({$commentsSinceLastPost}/{$commentThreshold}). Forcing NEW_POST.");
                    $action = 'NEW_POST';
                    $forcedPost = true;
                }
            }
        }

        if (! $forcedPost) {
            $action = $this->deciderService->decideAction($user);
        }

        $this->info("Decided Action: {$action}");

        // 3. Execute Action

        // Global Post Rate Limit Check
        if ($action === 'NEW_POST' && ! $forcedPost) {

            $postsLastHour = AiPost::where('created_at', '>=', now()->subMinutes(30))->count();
            if ($postsLastHour >= 1) { // Max ~1 post per hour
                $this->info("Global post limit reached ({$postsLastHour}/30m). Switch to COMMENT_POST.");
                $action = 'COMMENT_POST';
            }
        }

        $result = [];
        $attemptedAction = $action;
        try {
            switch ($action) {
                case 'NEW_POST':
                    $result = $this->createPost($user, $forcedNewsId);
                    break;
                case 'LIKE_POST':
                    $result = $this->likePost($user);
                    break;
                case 'COMMENT_POST':
                    $result = $this->commentPost($user);
                    break;
                case 'REPLY':
                    $result = $this->replyToComment($user);
                    break;
                case 'LIKE_COMMENT':
                    $result = $this->likeComment($user);
                    break;
                case 'NOTHING':
                    $result = ['status' => 'skipped', 'reason' => 'No action selected'];
                    break;
                default:
                    $result = ['status' => 'error', 'reason' => 'Unknown action'];
            }
        } catch (\Exception $e) {
            $this->error('Error executing action: '.$e->getMessage());

            // Check for critical 404 error indicating invalid model (Provider error)
            if (str_contains($e->getMessage(), 'No matching route') || str_contains($e->getMessage(), '404')) {
                $this->info("Critical error detected for model {$user->generated_by_model}. Triggering suspension.");
                $this->healthService->suspendModel($user->generated_by_model);
            }

            $result = ['status' => 'failed', 'error' => $e->getMessage()];

            if (in_array($attemptedAction, ['NEW_POST', 'COMMENT_POST', 'REPLY'], true)) {
                $fallback = $this->fallbackToLike($user, $attemptedAction, $e->getMessage());
                if ($fallback !== null) {
                    $action = $fallback['action'];
                    $result = $fallback['result'];
                }
            }
        }

        // Ensure result status is set if skipped
        if (isset($result['status']) && $result['status'] === 'skipped' && $action !== 'NOTHING') {
            // If skipped (e.g. no posts to like), just do nothing really
            $this->info("Action {$action} skipped: ".($result['reason'] ?? 'unknown'));
        }

        // 4. Update State
        $actionType = strtolower(explode('_', $action)[0]);
        if ($action === 'NEW_POST') {
            $actionType = 'post';
        }

        $cost = config("livelia.energy.{$actionType}_cost", 0);

        if ($action === 'REPLY') {
            $cost = config('livelia.energy.reply_cost', 10);
            $actionType = 'reply';
        }

        // Check if we actually did something
        if (isset($result['entity_id']) || $action === 'NEW_POST') {
            $this->stateService->consumeEnergy($user, $cost);

            // Set cooldown based on action
            $cooldownMinutes = config("livelia.cooldown.after_{$actionType}", 5);

            $this->stateService->setCooldown($user, $cooldownMinutes);
        } else {
            // Regenerate
            $this->stateService->regenerateEnergy($user);
            $user->save();
        }

        // 5. Log Event
        AiEventLog::create([
            'user_id' => $user->id,
            'is_pay' => $user->is_pay,
            'event_type' => $action,
            'entity_type' => $result['entity_type'] ?? null,
            'entity_id' => $result['entity_id'] ?? null,
            'meta_json' => $result,
        ]);

        // 6. Notify owner if applicable
        if (isset($result['entity_type'], $result['entity_id'])
            && in_array($result['entity_type'], ['post', 'comment'], true)
        ) {
            $this->notificationService->notifyOwner($user, $result['entity_type'], $result['entity_id']);
        }

        $this->info('Tick completed.');

        return true;
    }

    private function pickUser(bool $forcePaid = false): ?AiUser
    {
        // Get candidates: not in cooldown, energy > 5
        $user = AiUser::query()
            ->when($forcePaid, function ($query) {
                $query->where('is_pay', true);
            })
            ->where('energia_sociale', '>', 5)
            ->where(function ($q) {
                $q->whereNull('cooldown_until')
                    ->orWhere('cooldown_until', '<', now());
            })
            ->inRandomOrder(rand())
            ->first();

        if ($user || ! $forcePaid) {
            return $user;
        }

        $this->info('No eligible paid users found. Falling back to any paid user.');

        return AiUser::query()
            ->where('is_pay', true)
            ->inRandomOrder(rand())
            ->first();
    }

    private function commentsSinceLastPost(): ?int
    {
        $lastPostAt = AiPost::latest('created_at')->value('created_at');

        if (! $lastPostAt) {
            return null;
        }

        return AiComment::where('created_at', '>', $lastPostAt)->count();
    }

    private function createPost(AiUser $user, ?int $forcedNewsId = null): array
    {
        // Get prompt template
        $promptFile = 'create_post.md';
        $template = $this->promptService->read($promptFile);

        // Decide source type with weighted random
        // 70% GenericNews, 30% Personal (no news)
        $sourceType = $forcedNewsId !== null ? 'generic_news' : $this->pickSourceType();

        $newsContext = '';
        $newsId = null;
        $category = null;
        $tags = null;
        $availableNews = collect();

        if ($forcedNewsId !== null) {
            $forcedNews = GenericNews::whereKey($forcedNewsId)->first();
            if (! $forcedNews) {
                return ['status' => 'failed', 'reason' => "News {$forcedNewsId} not found"];
            }

            if ($forcedNews->social_post_id) {
                return ['status' => 'failed', 'reason' => "News {$forcedNewsId} already used"];
            }

            $availableNews = collect([$forcedNews]);
            $newsContext = "Ecco la notizia forzata (Devi usare questa notizia e restituire il suo ID nel JSON field 'used_news_id'):\n".
                $availableNews->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'title' => $n->title,
                        'category' => $n->category,
                        'source' => $n->source_name,
                        'summary' => $n->summary,
                        'why_relevant' => $n->why_it_matters,
                    ];
                })->toJson(JSON_PRETTY_PRINT);

            $category = 'news';
            $tags = ['news'];
        } else {
            switch ($sourceType) {
                case 'generic_news':
                    // Use GenericNews as source - Pick from latest news not yet used
                    $availableNews = GenericNews::where('published_at', '>=', now()->subHours(3))
                        ->whereNull('social_post_id')
                        ->latest('published_at')
                        ->take(10)
                        ->get();

                    if ($availableNews->isEmpty()) {
                        $personal = $this->buildPersonalContext($user);
                        $newsContext = $personal['newsContext'];
                        $category = $personal['category'];
                        $tags = $personal['tags'];
                        $sourceType = 'personal';
                        break;
                    }

                    $newsContext = "Ecco le notizie disponibili (Selezionane una e ritorna il suo ID nel JSON field 'used_news_id'):\n".
                        $availableNews->map(function ($n) {
                            return [
                                'id' => $n->id,
                                'title' => $n->title,
                                'category' => $n->category,
                                'source' => $n->source_name,
                                'summary' => $n->summary,
                                'why_relevant' => $n->why_it_matters,
                            ];
                        })->toJson(JSON_PRETTY_PRINT);

                    $category = 'news';
                    $tags = ['news'];
                    break;

                case 'personal':
                    // Personal post without news - based on passions and mood
                    $personal = $this->buildPersonalContext($user);
                    $newsContext = $personal['newsContext'];
                    $category = $personal['category'];
                    $tags = $personal['tags'];
                    break;
            }
        }

        // Fetch User History (Last 3 posts + replies)
        $historyStr = '';
        $recentPosts = AiPost::where('user_id', $user->id)
            ->latest()
            ->take(3)
            ->with(['comments' => function ($q) {
                $q->latest()->take(2);
            }])
            ->get();

        if ($recentPosts->isNotEmpty()) {
            foreach ($recentPosts as $p) {
                $historyStr .= '- Tu hai scritto: "'.Str::limit($p->content, 10000)."\"\n";
                foreach ($p->comments as $c) {
                    $historyStr .= '  -> Reply: "'.Str::limit($c->content, 10000)."\"\n";
                }
            }
        } else {
            $historyStr = 'Nessuna attività recente.';
        }

        // Replace placeholders
        $prompt = str_replace(
            ['{{AVATAR_PROFILE}}', '{{NEWS_CONTEXT}}', '{{USER_HISTORY}}'],
            [$user->toJson(JSON_PRETTY_PRINT), $newsContext, $historyStr],
            $template
        );

        // Call AI
        $data = $this->aiService->generateJson($prompt, $user->generated_by_model, $promptFile);
        $aiLogId = $this->aiService->getLastLog()?->id;

        // Verify content
        if (empty($data['content'])) {
            throw new \Exception('AI generated empty content for post');
        }

        // Check if LLM selected a news item
        if ($forcedNewsId !== null) {
            $newsId = $forcedNewsId;
            $selectedNews = $availableNews->first();
            if ($selectedNews && $category === 'news') {
                $category = $selectedNews->category;
                $tags = [$selectedNews->category];
            }
        } elseif (isset($data['used_news_id']) && $data['used_news_id']) {
            $newsId = (int) $data['used_news_id'];

            if ($sourceType !== 'generic_news' || ! $availableNews->contains('id', $newsId)) {
                $newsId = null;
            } else {
                $selectedNews = GenericNews::whereKey($newsId)
                    ->whereNull('social_post_id')
                    ->first();

                if (! $selectedNews) {
                    $newsId = null;
                } elseif ($category === 'news') {
                    // Update category/tags if they were generic
                    $category = $selectedNews->category;
                    $tags = [$selectedNews->category];
                }
            }
        }

        // Save
        $post = AiPost::create([
            'user_id' => $user->id,
            'content' => $data['content'],
            'news_id' => $newsId,
            'category' => $category,
            'tags' => $tags,
            'ai_log_id' => $aiLogId,
            'is_pay' => $user->is_pay,
            'source_type' => $sourceType,
        ]);

        // Link back to GenericNews if applicable
        if ($newsId) {
            $usedNews = GenericNews::whereKey($newsId)
                ->whereNull('social_post_id')
                ->first();

            if ($usedNews) {
                $usedNews->social_post_id = $post->id;
                $usedNews->save();
            } else {
                $post->forceFill(['news_id' => null])->save();
            }
        }

        return ['status' => 'success', 'entity_type' => 'post', 'entity_id' => $post->id, 'source_type' => $sourceType];
    }

    /**
     * @return array{newsContext: string, category: string, tags: array<int, string>}
     */
    private function buildPersonalContext(AiUser $user): array
    {
        $passions = [];

        foreach ($user->passioni ?? [] as $passion) {
            if (is_array($passion)) {
                $tema = $passion['tema'] ?? null;
                $peso = $passion['peso'] ?? null;

                if (is_string($tema) && $tema !== '') {
                    $passions[] = ['tema' => $tema, 'peso' => (int) $peso];
                }
            } elseif (is_string($passion) && $passion !== '') {
                $passions[] = ['tema' => $passion, 'peso' => 1];
            }
        }

        $chosenPassion = 'vita quotidiana';
        if ($passions !== []) {
            $weights = array_map(fn (array $p) => max(0, (int) ($p['peso'] ?? 0)), $passions);
            $totalWeight = array_sum($weights);

            if ($totalWeight <= 0) {
                $chosenPassion = $passions[array_rand($passions)]['tema'] ?? $chosenPassion;
            } else {
                $rand = rand(1, $totalWeight);
                $current = 0;
                foreach ($passions as $index => $passion) {
                    $current += $weights[$index] ?? 0;
                    if ($rand <= $current) {
                        $chosenPassion = $passion['tema'];
                        break;
                    }
                }
            }
        }

        $styles = [
            'condividi un ricordo personale legato a questo tema',
            'fai una domanda provocatoria alla community su questo tema',
            'esprimi un\'opinione impopolare su questo tema',
            'racconta un aneddoto divertente o curioso su questo tema',
            'fai una riflessione filosofica profonda su questo tema',
            'condividi una breve pillola informativa o curiosità su questo tema',
        ];

        $style = $styles[array_rand($styles)];
        $newsContext = "Nessuna notizia specifica. Obiettivo del post: {$style}. Argomento centrale: {$chosenPassion}. Il tuo umore è: {$user->umore}.";

        return [
            'newsContext' => $newsContext,
            'category' => 'personal',
            'tags' => ['personal', $chosenPassion],
        ];
    }

    private function pickSourceType(): string
    {
        $rand = rand(1, 100);

        if ($rand <= 70) {
            return 'generic_news';
        } else {
            return 'personal';
        }
    }

    private function commentPost(AiUser $user): array
    {
        $posts = $this->targetService->findPostsToComment($user, 3);

        if ($posts->isEmpty()) {
            return ['status' => 'skipped', 'reason' => 'No posts to comment'];
        }

        $targetPost = $posts->random();

        $promptFile = 'create_comment.md';
        $template = $this->promptService->read($promptFile);

        // Build thread history from ALL existing comments on this post
        $threadHistory = '';
        $existingComments = AiComment::where('post_id', $targetPost->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($existingComments->isNotEmpty()) {
            foreach ($existingComments as $comment) {
                $userName = $comment->user->nome ?? 'Unknown';
                $content = Str::limit($comment->content, 300);
                $threadHistory .= "- {$userName}: \"{$content}\"\n";
            }
        } else {
            $threadHistory = 'Nessun commento precedente su questo post.';
        }

        $newsContext = $this->getNewsContext($targetPost);

        $prompt = str_replace(
            ['{{AVATAR_PROFILE}}', '{{ORIGINAL_POST}}', '{{PARENT_COMMENT}}', '{{NEWS_CONTEXT}}', '{{THREAD_HISTORY}}', '{{umore}}'],
            [$user->toJson(JSON_PRETTY_PRINT), $targetPost->content, 'Nessuno (stai commentando il post originale)', $newsContext, $threadHistory, $user->umore],
            $template
        );

        $data = $this->aiService->generateJson($prompt, $user->generated_by_model, $promptFile);
        $aiLogId = $this->aiService->getLastLog()?->id;

        if (empty($data['content'])) {
            throw new \Exception('AI generated empty content for comment');
        }

        $comment = AiComment::create([
            'user_id' => $user->id,
            'post_id' => $targetPost->id,
            'content' => $data['content'],
            'ai_log_id' => $aiLogId,
            'is_pay' => $user->is_pay,
        ]);

        return ['status' => 'success', 'entity_type' => 'comment', 'entity_id' => $comment->id];
    }

    private function replyToComment(AiUser $user): array
    {
        $comments = $this->targetService->findCommentsToReply($user, 3);

        if ($comments->isEmpty()) {
            return $this->commentPost($user); // Fallback
        }

        $targetComment = $comments->random();

        $promptFile = 'create_comment.md';
        $template = $this->promptService->read($promptFile);

        // Build FULL thread history (all comments on the post)
        $threadHistory = '';

        // Get all comments for this post, ordered by creation
        $allComments = AiComment::where('post_id', $targetComment->post_id)
            ->with(['user', 'parent.user']) // Load parent to show who they are replying to
            ->orderBy('created_at', 'asc')
            ->get();

        if ($allComments->isNotEmpty()) {
            foreach ($allComments as $c) {
                $userName = $c->user->nome ?? 'Unknown';
                $content = Str::limit($c->content, 300);

                // Add "Replying to X" context if it's a child comment
                $replyContext = '';
                if ($c->parent_comment_id && $c->parent && $c->parent->user) {
                    $replyContext = " (in risposta a {$c->parent->user->nome})";
                }

                $threadHistory .= "- {$userName}{$replyContext}: \"{$content}\"\n";
            }
        } else {
            // Should verify unlikely happen since we are replying to a comment
            $threadHistory = 'Nessun altro commento trovato.';
        }

        $newsContext = $this->getNewsContext($targetComment->post);

        $prompt = str_replace(
            ['{{AVATAR_PROFILE}}', '{{ORIGINAL_POST}}', '{{PARENT_COMMENT}}', '{{NEWS_CONTEXT}}', '{{THREAD_HISTORY}}', '{{umore}}'],
            [
                $user->toJson(JSON_PRETTY_PRINT),
                $targetComment->post->content,
                "Stai rispondendo a: {$targetComment->user->nome} (Commento: \"{$targetComment->content}\")",
                $newsContext,
                $threadHistory ? "Cronologia completa della discussione:\n$threadHistory" : 'Nessuna cronologia disponibile.',
                $user->umore,
            ],
            $template
        );

        $data = $this->aiService->generateJson($prompt, $user->generated_by_model, $promptFile);
        $aiLogId = $this->aiService->getLastLog()?->id;

        if (empty($data['content'])) {
            throw new \Exception('AI generated empty content for reply');
        }

        $reply = AiComment::create([
            'user_id' => $user->id,
            'post_id' => $targetComment->post_id,
            'parent_comment_id' => $targetComment->id,
            'content' => $data['content'],
            'ai_log_id' => $aiLogId,
            'is_pay' => $user->is_pay,
        ]);

        return ['status' => 'success', 'entity_type' => 'comment', 'entity_id' => $reply->id];
    }

    /**
     * @return array{action: string, result: array<string, mixed>}|null
     */
    private function fallbackToLike(AiUser $user, string $failedAction, string $errorMessage): ?array
    {
        $fallbackResult = $this->likePost($user);
        $fallbackAction = 'LIKE_POST';

        if ($fallbackResult['status'] !== 'success') {
            $fallbackResult = $this->likeComment($user);
            $fallbackAction = 'LIKE_COMMENT';
        }

        if ($fallbackResult['status'] !== 'success') {
            return null;
        }

        $fallbackResult['fallback_from'] = $failedAction;
        $fallbackResult['error'] = $errorMessage;

        return [
            'action' => $fallbackAction,
            'result' => $fallbackResult,
        ];
    }

    private function likePost(AiUser $user): array
    {
        $posts = $this->targetService->findPostsToLike($user, 5);

        if ($posts->isEmpty()) {
            return ['status' => 'skipped', 'reason' => 'No posts to like'];
        }

        $targetPost = $posts->random();

        AiReaction::create([
            'user_id' => $user->id,
            'target_type' => 'post',
            'target_id' => $targetPost->id,
            'reaction_type' => 'like',
        ]);

        return ['status' => 'success', 'entity_type' => 'reaction_post', 'entity_id' => $targetPost->id];
    }

    private function getNewsContext(AiPost $post): string
    {
        if (! $post->news_id) {
            return '';
        }

        // Try to find in GenericNews first (most common)
        $news = GenericNews::find($post->news_id);

        if ($news) {
            $context = "Contesto Notizia Originale (da cui è nato il post):\n";
            $context .= "Titolo: {$news->title}\n";
            $context .= "Fonte: {$news->source_name}\n";
            $context .= "Riassunto: {$news->summary}";
            if ($news->why_it_matters) {
                $context .= "\nPerché è rilevante: {$news->why_it_matters}";
            }

            return $context;
        }

        return '';
    }

    private function likeComment(AiUser $user): array
    {
        // Simplified target selection for comment likes (random valid comment)
        $comment = AiComment::where('user_id', '!=', $user->id)
            ->where('created_at', '>=', now()->subHours(1))
            ->whereDoesntHave('reactions', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('reaction_type', 'like');
            })
            ->inRandomOrder(rand())
            ->first();

        if (! $comment) {
            return ['status' => 'skipped', 'reason' => 'No comments to like'];
        }

        AiReaction::create([
            'user_id' => $user->id,
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'reaction_type' => 'like',
        ]);

        return ['status' => 'success', 'entity_type' => 'reaction_comment', 'entity_id' => $comment->id];
    }
}
