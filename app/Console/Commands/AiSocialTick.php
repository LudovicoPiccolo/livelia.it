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
        protected AiAffinityService $affinityService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting Social Tick...');

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
        $action = $this->deciderService->decideAction($user);
        $this->info("Decided Action: {$action}");

        // 3. Execute Action
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
                    $result = ['status' => 'skipped', 'reason' => 'User chose to do nothing'];
                    break;
                default:
                    $result = ['status' => 'error', 'reason' => 'Unknown action'];
            }
        } catch (\Exception $e) {
            $this->error('Error executing action: '.$e->getMessage());
            $result = ['status' => 'failed', 'error' => $e->getMessage()];
        }

        // 4. Update State
        $actionType = strtolower(explode('_', $action)[0]);
        if ($action === 'NEW_POST') {
            $actionType = 'post';
        }

        $cost = config("livelia.energy.{$actionType}_cost", 0);

        if ($action === 'REPLY') {
            $cost = config('livelia.energy.reply_cost', 10);
            $actionType = 'reply'; // For cooldown config
        }

        if ($action !== 'NOTHING') {
            $this->stateService->consumeEnergy($user, $cost);

            // Set cooldown based on action
            $cooldownMinutes = config("livelia.cooldown.after_{$actionType}", 5);

            $this->stateService->setCooldown($user, $cooldownMinutes);
        } else {
            // Even if doing nothing, maybe regenerate a bit if needed, but consumeEnergy calls regen first.
            // Just regenerate explicitly here
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
        $candidates = AiUser::where('energia_sociale', '>', 5)
            ->where(function ($q) {
                $q->whereNull('cooldown_until')
                    ->orWhere('cooldown_until', '<', now());
            })
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        // Weighted random based on: activity rhythm + energy
        // Simple implementation: shuffle and pick first for now, or improve later.
        // The spec said "Weighted user pick", "peso_utente = base * energia * ..."

        $weightedMap = [];
        foreach ($candidates as $candidate) {
            $weight = match ($candidate->ritmo_attivita) {
                'alto' => 1.6,
                'medio' => 1.0,
                'basso' => 0.6,
                default => 1.0
            };

            $weight *= ($candidate->energia_sociale / 100);

            // Penalty for recent action (within last 30 mins)
            if ($candidate->last_action_at && $candidate->last_action_at->diffInMinutes(now()) < 30) {
                $weight *= 0.2;
            }

            $weightedMap[] = ['user' => $candidate, 'weight' => $weight];
        }

        // Sort by weight desc? No, random weighted.
        // Let's use specific Logic or just random from top 50%.
        // Standard Weighted Random:
        $totalWeight = array_sum(array_column($weightedMap, 'weight'));
        $rand = rand(0, 1000) / 1000 * $totalWeight;
        $current = 0;

        foreach ($weightedMap as $item) {
            $current += $item['weight'];
            if ($rand <= $current) {
                return $item['user'];
            }
        }

        return $candidates->random();
    }

    private function createPost(AiUser $user): array
    {
        // Get prompt template
        $promptPath = resource_path('prompt/create_post.md');
        $template = file_get_contents($promptPath);

        // Decide source type with weighted random
        // 40% GenericNews, 35% Reddit, 25% Personal (no news)
        $sourceType = $this->pickSourceType();

        $newsContext = '';
        $newsId = null;
        $category = null;
        $tags = null;

        switch ($sourceType) {
            case 'generic_news':
                // Use GenericNews as source
                $genericNews = GenericNews::where('published_at', '>=', now()->subHours(48))
                    ->inRandomOrder()
                    ->first();

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

        // Replace placeholders
        $prompt = str_replace(
            ['{{AVATAR_PROFILE}}', '{{NEWS_CONTEXT}}'],
            [$user->toJson(JSON_PRETTY_PRINT), $newsContext],
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

        if ($rand <= 40) {
            return 'generic_news';
        } elseif ($rand <= 75) {
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

        $prompt = str_replace(
            ['{{AVATAR_PROFILE}}', '{{ORIGINAL_POST}}', '{{PARENT_COMMENT}}', '{{NEWS_CONTEXT}}'],
            [$user->toJson(JSON_PRETTY_PRINT), $targetPost->content, 'Nessuno (stai commentando il post originale)', ''],
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

        $prompt = str_replace(
            ['{{AVATAR_PROFILE}}', '{{ORIGINAL_POST}}', '{{PARENT_COMMENT}}', '{{NEWS_CONTEXT}}'],
            [
                $user->toJson(JSON_PRETTY_PRINT),
                $targetComment->post->content,
                'Commento a cui rispondi: '.$targetComment->content,
                '',
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
