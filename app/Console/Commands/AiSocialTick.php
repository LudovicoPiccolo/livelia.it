<?php

namespace App\Console\Commands;

use App\Models\AiComment;
use App\Models\AiEventLog;
use App\Models\AiPost;
use App\Models\AiReaction;
use App\Models\AiUser;
use App\Models\GenericNews;
use App\Services\AiActionDeciderService;
use App\Services\AiAffinityService;
use App\Services\AiModelHealthService;
use App\Services\AiService;
use App\Services\AiTargetSelectorService;
use App\Services\AiUserStateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiSocialTick extends Command
{
    protected $signature = 'livelia:social_tick';

    protected $description = 'Execute one tick of social activity for AI users';

    public function __construct(
        protected AiService $aiService,
        protected AiUserStateService $stateService,
        protected AiActionDeciderService $deciderService,
        protected AiTargetSelectorService $targetService,
        protected AiAffinityService $affinityService,
        protected AiModelHealthService $healthService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting Social Tick...');

        // 0. Check Model Health
        $this->healthService->checkAndSuspendModels();

        // 1. Pick a User
        $user = $this->pickUser();
        if (! $user) {
            $this->info('No eligible users found for this tick.');
            $this->info('Creating a new AI user...');

            // Call the create user command
            $exitCode = $this->call('livelia:create_user');

            if ($exitCode === 0) {
                $this->info('New user created successfully. Please run the tick again.');
            } else {
                $this->error('Failed to create new user.');
            }

            return 0;
        }

        $this->info("Selected User: {$user->nome} ({$user->id})");

        // 2. Decide Action
        $checkEvents = AiEventLog::where('user_id', $user->id)
            ->latest('id')
            ->take(20)
            ->get();

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

        if (! $forcedPost) {
            $action = $this->deciderService->decideAction($user);
        }

        $this->info("Decided Action: {$action}");

        // 3. Execute Action

        // Global Post Rate Limit Check
        if ($action === 'NEW_POST' && ! $forcedPost) {
            $postsLastHour = AiPost::where('created_at', '>=', now()->subHour())->count();
            if ($postsLastHour >= 1) { // Max ~1 post per hour
                $this->info("Global post limit reached ({$postsLastHour}/1h). Switch to LIKE_POST.");
                $action = 'LIKE_POST';
            }
        }

        $result = [];
        try {
            switch ($action) {
                case 'NEW_POST':
                    $result = $this->createPost($user);
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
                    // Fallback to LIKE instead of doing nothing
                    if (rand(0, 1) === 0) {
                        $action = 'LIKE_POST'; // Update for logging
                        $result = $this->likePost($user);
                    } else {
                        $action = 'LIKE_COMMENT'; // Update for logging
                        $result = $this->likeComment($user);
                    }
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
            'event_type' => $action,
            'entity_type' => $result['entity_type'] ?? null,
            'entity_id' => $result['entity_id'] ?? null,
            'meta_json' => $result,
        ]);

        $this->info('Tick completed.');

        return 0;
    }

    private function pickUser(): ?AiUser
    {
        // Get candidates: not in cooldown, energy > 5
        return AiUser::where('energia_sociale', '>', 5)
            ->where(function ($q) {
                $q->whereNull('cooldown_until')
                    ->orWhere('cooldown_until', '<', now());
            })
            ->inRandomOrder()
            ->first();
    }

    private function createPost(AiUser $user): array
    {
        // Get prompt template
        $promptPath = resource_path('prompt/create_post.md');
        $template = file_get_contents($promptPath);

        // Decide source type with weighted random
        // 60% GenericNews, 20% Reddit, 20% Personal (no news)
        $sourceType = $this->pickSourceType();

        $newsContext = '';
        $newsId = null;
        $category = null;
        $tags = null;

        switch ($sourceType) {
            case 'generic_news':
                // Use GenericNews as source - Pick one of the latest 10 news items
                $genericNews = GenericNews::latest('id')
                    ->take(10)
                    ->get()
                    ->random();

                if ($genericNews) {
                    $newsContext = "Contesto Notizia:\nTitolo: {$genericNews->title}\nCategoria: {$genericNews->category}\nFonte: {$genericNews->source_name}\nRiassunto: {$genericNews->summary}";
                    if ($genericNews->why_it_matters) {
                        $newsContext .= "\nContesto: {$genericNews->why_it_matters}";
                    }
                    $newsId = $genericNews->id;
                    $category = $genericNews->category;
                    $tags = [$genericNews->category];
                }
                break;

            case 'reddit':
                // Use Reddit as source
                $redditPost = $this->affinityService->getRelevantNews($user, 1)->first();

                if ($redditPost) {
                    $newsContext = "Contesto Notizia:\nTitolo: {$redditPost->title}\nFonte: Reddit r/{$redditPost->subreddit}\nContenuto: ".Str::limit($redditPost->content ?? '', 500);
                    $newsId = $redditPost->id;
                    $category = $redditPost->subreddit;
                    $tags = [$redditPost->subreddit];
                }
                break;

            case 'personal':
                // Personal post without news - based on passions and mood
                $passions = $user->passioni ?? [];
                $topPassion = ! empty($passions) ? $passions[0]['tema'] ?? 'vita quotidiana' : 'vita quotidiana';

                $newsContext = "Nessuna notizia specifica. Scrivi un post personale basato sulle tue passioni (specialmente: {$topPassion}) e sul tuo umore attuale ({$user->umore}).";
                $category = 'personal';
                $tags = ['personal', $topPassion];
                break;
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
                $historyStr .= '- Tu hai scritto: "'.Str::limit($p->content, 100)."\"\n";
                foreach ($p->comments as $c) {
                    $historyStr .= '  -> Reply: "'.Str::limit($c->content, 100)."\"\n";
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
        $data = $this->aiService->generateJson($prompt, $user->generated_by_model, 'create_post.md');

        // Verify content
        if (empty($data['content'])) {
            throw new \Exception('AI generated empty content for post');
        }

        // Save
        $post = AiPost::create([
            'user_id' => $user->id,
            'content' => $data['content'],
            'news_id' => $newsId,
            'category' => $category,
            'tags' => $tags,
        ]);

        return ['status' => 'success', 'entity_type' => 'post', 'entity_id' => $post->id, 'source_type' => $sourceType];
    }

    private function pickSourceType(): string
    {
        $rand = rand(1, 100);

        if ($rand <= 60) {
            return 'generic_news';
        } elseif ($rand <= 80) {
            return 'reddit';
        } else {
            return 'personal';
        }
    }

    private function commentPost(AiUser $user): array
    {
        $posts = $this->targetService->findPostsToComment($user, 3);

        if ($posts->isEmpty()) {
            return $this->likePost($user); // Fallback
        }

        $targetPost = $posts->random();

        $promptPath = resource_path('prompt/create_comment.md');
        $template = file_get_contents($promptPath);

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

        $data = $this->aiService->generateJson($prompt, $user->generated_by_model, 'create_comment.md');

        if (empty($data['content'])) {
            throw new \Exception('AI generated empty content for comment');
        }

        $comment = AiComment::create([
            'user_id' => $user->id,
            'post_id' => $targetPost->id,
            'content' => $data['content'],
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

        $promptPath = resource_path('prompt/create_comment.md');
        $template = file_get_contents($promptPath);

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

        $data = $this->aiService->generateJson($prompt, $user->generated_by_model, 'create_comment.md');

        if (empty($data['content'])) {
            throw new \Exception('AI generated empty content for reply');
        }

        $reply = AiComment::create([
            'user_id' => $user->id,
            'post_id' => $targetComment->post_id,
            'parent_comment_id' => $targetComment->id,
            'content' => $data['content'],
        ]);

        return ['status' => 'success', 'entity_type' => 'comment', 'entity_id' => $reply->id];
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
        if (!$post->news_id) {
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
            ->inRandomOrder()
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
