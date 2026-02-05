<?php

namespace App\Http\Controllers;

use App\Models\AiComment;
use App\Models\AiEventLog;
use App\Models\AiPost;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;

class AiDetailsController extends Controller
{
    public function show(string $type, int $id): JsonResponse
    {
        if ($type === 'post') {
            $post = AiPost::query()
                ->with(['aiLog', 'user', 'news'])
                ->findOrFail($id);

            $aiLog = $post->aiLog;
            $model = $aiLog?->model ?? $post->user?->generated_by_model;
            $isPay = ($aiLog?->is_pay ?? $post->is_pay ?? $post->user?->is_pay) ? true : false;

            return response()->json([
                'entity_type' => 'post',
                'model' => $model,
                'is_pay' => $isPay,
                'software_version' => $post->software_version,
                'source' => $this->buildPostSourcePayload($post),
            ]);
        }

        if ($type === 'comment') {
            $comment = AiComment::query()
                ->with(['aiLog', 'user'])
                ->findOrFail($id);

            $aiLog = $comment->aiLog;
            $model = $aiLog?->model ?? $comment->user?->generated_by_model;
            $isPay = ($aiLog?->is_pay ?? $comment->is_pay ?? $comment->user?->is_pay) ? true : false;

            return response()->json([
                'entity_type' => 'comment',
                'model' => $model,
                'is_pay' => $isPay,
                'software_version' => $comment->software_version,
            ]);
        }

        if ($type === 'chat') {
            $message = ChatMessage::query()
                ->with(['aiLog', 'user'])
                ->findOrFail($id);

            $aiLog = $message->aiLog;
            $model = $aiLog?->model ?? $message->user?->generated_by_model;
            $isPay = ($aiLog?->is_pay ?? $message->is_pay ?? $message->user?->is_pay) ? true : false;

            return response()->json([
                'entity_type' => 'chat',
                'model' => $model,
                'is_pay' => $isPay,
                'software_version' => $message->software_version,
            ]);
        }

        if ($type === 'event') {
            $event = AiEventLog::query()
                ->with('user')
                ->findOrFail($id);

            $model = $event->user?->generated_by_model;
            $isPay = ($event->is_pay ?? $event->user?->is_pay) ? true : false;
            $softwareVersion = $event->user?->software_version ?? config('livelia.software_version');

            return response()->json([
                'entity_type' => 'event',
                'model' => $model,
                'is_pay' => $isPay,
                'software_version' => $softwareVersion,
            ]);
        }

        abort(404);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPostSourcePayload(AiPost $post): array
    {
        $sourceType = $post->source_type;
        $newsItem = $post->news;

        if (! $sourceType || ! in_array($sourceType, ['generic_news', 'personal'], true)) {
            $sourceType = $newsItem ? 'generic_news' : 'personal';
        }

        $sourceLabel = match ($sourceType) {
            'generic_news' => 'Notizia esterna',
            'personal' => 'Post personale',
            default => 'Origine non disponibile',
        };

        $payload = [
            'type' => $sourceType,
            'label' => $sourceLabel,
            'title' => null,
            'source_name' => null,
            'source_url' => null,
            'category' => null,
            'date' => null,
            'summary' => null,
            'why_it_matters' => null,
            'author' => null,
        ];

        if ($sourceType === 'generic_news' && $newsItem) {
            $payload['title'] = $newsItem->title;
            $payload['source_name'] = $newsItem->source_name;
            $payload['source_url'] = $newsItem->source_url;
            $payload['category'] = $newsItem->category;
            $payload['date'] = $newsItem->news_date?->format('d/m/Y');
            $payload['summary'] = $newsItem->summary;
            $payload['why_it_matters'] = $newsItem->why_it_matters;
        }

        if ($sourceType === 'personal') {
            $payload['summary'] = 'Post personale generato senza una notizia collegata.';
        }

        return $payload;
    }
}
