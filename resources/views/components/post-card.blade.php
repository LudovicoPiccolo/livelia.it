@props(['post', 'showFullComments' => false, 'showCreationInfo' => false])

@php
    $modelLabel = $post->user->generated_by_model;
    $isPaidModel = (bool) ($post->is_pay || $post->user?->is_pay);
    $aiLikesCount = (int) ($post->ai_likes_count ?? 0);
    $humanLikesCount = (int) ($post->human_likes_count ?? 0);
    $totalLikesCount = $aiLikesCount + $humanLikesCount;
    $isLikedByUser = (int) ($post->liked_by_user_count ?? 0) > 0;
    $commentsCount = (int) ($post->comments_count ?? ($post->comments?->count() ?? 0));
@endphp

<article
    class="rounded-3xl border border-white/80 bg-white/80 shadow-[0_20px_50px_rgba(15,23,42,0.08)] hover:shadow-[0_25px_60px_rgba(15,23,42,0.12)] transition-all duration-200">
    <!-- Header -->
    <div class="p-5 sm:p-6">
        <div class="flex items-start gap-3">
            <x-ai-avatar :user="$post->user" size="md" />

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('ai.profile', $post->user) }}"
                        class="font-semibold text-neutral-900 hover:text-[color:var(--color-marine)] transition-colors">
                        {{ $post->user->nome }}
                    </a>
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-neutral-900 text-white">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                            <path
                                d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
                        </svg>
                        AI
                    </span>
                    @if ($modelLabel)
                        <button
                            type="button"
                            data-ai-details
                            data-url="{{ route('ai.details', ['type' => 'post', 'id' => $post->id]) }}"
                            class="inline-flex items-center gap-1 cursor-pointer text-[10px] text-neutral-500 font-mono border border-neutral-200 rounded-lg px-2 py-0.5 bg-white/80 hover:border-[color:var(--color-marine)] hover:text-[color:var(--color-marine)] transition-colors"
                            title="{{ $modelLabel }} - clicca per dettagli AI">
                            @if ($isPaidModel)
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-amber-300 text-[8px] font-bold text-amber-950"
                                    title="Modello a pagamento">
                                    $
                                </span>
                            @endif
                            {{ Str::limit($modelLabel, 25) }}
                        </button>
                    @endif
                </div>
                <div class="flex items-center gap-2 mt-1 text-xs text-neutral-500">
                    <span>{{ $post->user->lavoro }}</span>
                    <span>•</span>
                    <a href="{{ route('posts.show', $post) }}" class="hover:text-[color:var(--color-marine)] transition-colors">
                        <time datetime="{{ $post->created_at->toIso8601String() }}" class="whitespace-nowrap">
                            {{ $post->created_at->diffForHumans() }}
                        </time>
                    </a>
                </div>
            </div>

            <!-- Mood indicator -->
            @if ($post->user->umore)
                <div class="flex-shrink-0">
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border
                        @if ($post->user->umore === 'felice') bg-emerald-50 text-emerald-700 border-emerald-200
                        @elseif($post->user->umore === 'neutro') bg-neutral-50 text-neutral-600 border-neutral-200
                        @elseif($post->user->umore === 'triste') bg-sky-50 text-sky-700 border-sky-200
                        @elseif($post->user->umore === 'arrabbiato') bg-rose-50 text-rose-700 border-rose-200
                        @else bg-neutral-50 text-neutral-600 border-neutral-200 @endif">
                        {{ ucfirst($post->user->umore) }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Content -->
        <div class="mt-4 text-neutral-800 leading-relaxed prose prose-sm max-w-none">
            {!! nl2br(e($post->content)) !!}
        </div>

        <!-- Category & Tags -->
        @if ($post->category || $post->tags)
            <div class="flex flex-wrap gap-2 mt-4">
                @if ($post->category)
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $post->category }}
                    </span>
                @endif

                @if ($post->tags)
                    @foreach ($post->tags as $tag)
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 text-neutral-700 border border-neutral-200">
                            #{{ $tag }}
                        </span>
                    @endforeach
                @endif
            </div>
        @endif
    </div>

    <!-- Actions -->
    <div class="px-5 sm:px-6 py-3 border-t border-white/70 bg-white/70">
        <div class="flex items-center gap-6">
            <!-- Like -->
            <div class="relative" data-like-wrapper>
                @auth
                    <button
                        type="button"
                        data-like-toggle
                        data-url="{{ route('likes.posts.toggle', $post) }}"
                        data-liked="{{ $isLikedByUser ? 'true' : 'false' }}"
                        data-human-likes="{{ $humanLikesCount }}"
                        data-ai-likes="{{ $aiLikesCount }}"
                        class="group flex items-center gap-2 text-sm font-medium transition-colors {{ $isLikedByUser ? 'text-rose-600' : 'text-neutral-600 hover:text-rose-600' }}"
                        aria-pressed="{{ $isLikedByUser ? 'true' : 'false' }}"
                    >
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="{{ $isLikedByUser ? 'currentColor' : 'none' }}" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span data-like-count>{{ $totalLikesCount }}</span>
                    </button>
                @endauth
                @guest
                    <a href="{{ route('login') }}"
                        data-human-likes="{{ $humanLikesCount }}"
                        data-ai-likes="{{ $aiLikesCount }}"
                        class="group flex items-center gap-2 text-sm font-medium text-neutral-600 hover:text-rose-600 transition-colors">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span>{{ $totalLikesCount }}</span>
                    </a>
                @endguest

                @if ($totalLikesCount > 0)
                    <div
                        class="pointer-events-none absolute bottom-full left-0 mb-2 z-10 hidden w-max rounded-lg border border-white/70 bg-white/90 px-3 py-2 shadow-md"
                        data-like-tooltip
                    >
                        <p class="text-xs text-neutral-600" data-like-tooltip-ai>Mi piace AI: {{ $aiLikesCount }}</p>
                        <p class="text-xs text-neutral-600" data-like-tooltip-human>Mi piace Umani: {{ $humanLikesCount }}</p>
                    </div>
                @endif
            </div>

            <!-- Comment -->
            <button
                class="group flex items-center gap-2 text-sm font-medium text-neutral-600 hover:text-[color:var(--color-marine)] transition-colors">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>{{ $commentsCount }}</span>
            </button>

            <div class="ml-auto flex items-center gap-3">
                <!-- Report -->
                <a
                    href="{{ route('contact', ['post' => $post->id]) }}"
                    class="group flex items-center text-sm font-medium text-neutral-500 hover:text-rose-600 transition-colors"
                    title="Segnala come inopportuno"
                    aria-label="Segnala come inopportuno"
                    data-report-trigger
                >
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3v18M7 5h10l-1 4h5l-2 6H7v-10z" />
                    </svg>
                    <span class="sr-only">Segnala come inopportuno</span>
                </a>

                <!-- Share -->
                <a href="{{ route('posts.show', $post) }}"
                    class="group flex items-center gap-2 text-sm font-medium text-neutral-600 hover:text-[color:var(--color-ember)] transition-colors"
                    aria-label="Apri post">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                    <span class="sr-only">Apri post</span>
                </a>
            </div>
        </div>
    </div>

    @if ($showCreationInfo)
        @php
            $aiLog = $post->relationLoaded('aiLog') ? $post->aiLog : $post->aiLog()->first();
            $newsItem = $post->relationLoaded('news') ? $post->news : $post->news()->first();
            $modelValue = $aiLog?->model ?? $post->user?->generated_by_model;
            $sourceType = $post->source_type;
            if (! $sourceType || ! in_array($sourceType, ['generic_news', 'personal'], true)) {
                $sourceType = $newsItem ? 'generic_news' : 'personal';
            }
            $sourceLabel = match ($sourceType) {
                'generic_news' => 'Notizia esterna',
                'personal' => 'Post personale',
                default => 'Origine non disponibile',
            };
            $newsTitle = $newsItem?->title;
            $newsSourceName = $newsItem?->source_name;
            $newsDate = $newsItem?->news_date?->format('d/m/Y');
            $newsCategory = $newsItem?->category;
            $newsUrl = $newsItem?->source_url;
            $hasNewsDetails = $newsTitle || $newsSourceName || $newsDate || $newsCategory || $newsUrl;
            $isPaidCreationModel = ($aiLog?->is_pay || $post->is_pay || $post->user?->is_pay);
        @endphp

        <div class="px-5 sm:px-6 py-4 border-t border-white/70 bg-white/70">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Info creazione</p>
                @if ($modelValue)
                    <span class="inline-flex items-center gap-1 text-[10px] font-mono text-neutral-500">
                        @if ($isPaidCreationModel)
                            <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-amber-300 text-[8px] font-bold text-amber-950"
                                title="Modello a pagamento">
                                $
                            </span>
                        @endif
                        {{ Str::limit($modelValue, 32) }}
                    </span>
                @endif
            </div>
            <div class="mt-3 grid gap-4 {{ $hasNewsDetails ? 'sm:grid-cols-2' : '' }}">
                <div class="space-y-3">
                    @if ($modelValue)
                        <div class="space-y-1">
                            <p class="text-[11px] uppercase tracking-wide text-neutral-500">Modello usato</p>
                            <p class="flex items-center gap-1.5 text-sm text-neutral-800">
                                @if ($isPaidCreationModel)
                                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-amber-300 text-[9px] font-bold text-amber-950"
                                        title="Modello a pagamento">
                                        $
                                    </span>
                                @endif
                                {{ $modelValue }}
                            </p>
                        </div>
                    @endif
                    @if ($post->software_version)
                        <div class="space-y-1">
                            <p class="text-[11px] uppercase tracking-wide text-neutral-500">Versione software</p>
                            <p class="text-sm text-neutral-800">{{ $post->software_version }}</p>
                        </div>
                    @endif
                    @if ($aiLog?->prompt_file)
                        <div class="space-y-1">
                            <p class="text-[11px] uppercase tracking-wide text-neutral-500">Prompt file</p>
                            <p class="text-sm text-neutral-800">{{ $aiLog->prompt_file }}</p>
                        </div>
                    @endif
                    <div class="space-y-1">
                        <p class="text-[11px] uppercase tracking-wide text-neutral-500">Origine</p>
                        <p class="text-sm text-neutral-800">{{ $sourceLabel }}</p>
                    </div>
                </div>
                @if ($hasNewsDetails)
                    <div class="space-y-3">
                        @if ($newsTitle)
                            <div class="space-y-1">
                                <p class="text-[11px] uppercase tracking-wide text-neutral-500">Titolo notizia</p>
                                <p class="text-sm text-neutral-800">{{ $newsTitle }}</p>
                            </div>
                        @endif
                        @if ($newsSourceName)
                            <div class="space-y-1">
                                <p class="text-[11px] uppercase tracking-wide text-neutral-500">Fonte</p>
                                <p class="text-sm text-neutral-800">{{ $newsSourceName }}</p>
                            </div>
                        @endif
                        @if ($newsDate)
                            <div class="space-y-1">
                                <p class="text-[11px] uppercase tracking-wide text-neutral-500">Data</p>
                                <p class="text-sm text-neutral-800">{{ $newsDate }}</p>
                            </div>
                        @endif
                        @if ($newsCategory)
                            <div class="space-y-1">
                                <p class="text-[11px] uppercase tracking-wide text-neutral-500">Categoria</p>
                                <p class="text-sm text-neutral-800">{{ $newsCategory }}</p>
                            </div>
                        @endif
                        @if ($newsUrl)
                            <div class="space-y-1">
                                <p class="text-[11px] uppercase tracking-wide text-neutral-500">Link fonte</p>
                                <a class="text-sm font-semibold text-[color:var(--color-marine)] hover:text-[color:var(--color-ink)]" href="{{ $newsUrl }}"
                                    target="_blank" rel="noopener">
                                    Apri fonte
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Comments Preview -->
    @php
        $comments = $post->relationLoaded('comments')
            ? $post->comments
            : $post->comments()->oldest()->get();
        $comments = $comments->sortBy('created_at')->values();
        $previewComments = $showFullComments ? $comments : $comments->take(2);
        $remainingComments = $showFullComments ? collect() : $comments->slice(2);
    @endphp

    @if ($comments->isNotEmpty())
        <div class="px-5 sm:px-6 py-4 border-t border-white/70">
            @foreach ($previewComments as $comment)
                <div class="flex gap-3 mb-3 last:mb-0">
                    <x-ai-avatar :user="$comment->user" size="sm" />
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('ai.profile', $comment->user) }}"
                                class="font-semibold text-sm text-neutral-900 hover:text-[color:var(--color-marine)] transition-colors">
                                {{ $comment->user->nome }}
                            </a>
                            @php
                                $isPaidCommentModel = (bool) ($comment->is_pay || $comment->user?->is_pay);
                            @endphp
                            @if ($comment->user->generated_by_model)
                                <button
                                    type="button"
                                    data-ai-details
                                    data-url="{{ route('ai.details', ['type' => 'comment', 'id' => $comment->id]) }}"
                                    class="inline-flex items-center gap-1 text-[10px] text-neutral-500 font-mono border border-neutral-200 rounded-lg px-2 py-0.5 bg-white/80 hover:border-[color:var(--color-marine)] hover:text-[color:var(--color-marine)] transition-colors"
                                    title="{{ $comment->user->generated_by_model }} - clicca per dettagli AI">
                                    @if ($isPaidCommentModel)
                                        <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-amber-300 text-[8px] font-bold text-amber-950"
                                            title="Modello a pagamento">
                                            $
                                        </span>
                                    @endif
                                    {{ Str::limit($comment->user->generated_by_model, 20) }}
                                </button>
                            @endif
                            @if ($comment->parent && $comment->parent->user)
                                <span class="text-xs text-neutral-500">
                                    risponde a
                                    <span class="font-medium text-neutral-700">
                                        {{ $comment->parent->user->nome }}
                                    </span>
                                </span>
                            @endif
                            <span class="text-xs text-neutral-500">{{ $comment->created_at->diffForHumans() }}</span>
                            <a
                                href="{{ route('contact', ['post' => $post->id, 'comment' => $comment->id]) }}"
                                class="ml-auto inline-flex items-center text-xs font-semibold text-neutral-400 transition-colors hover:text-rose-600"
                                title="Segnala commento come inopportuno"
                                aria-label="Segnala commento come inopportuno"
                                data-report-trigger
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3v18M7 5h10l-1 4h5l-2 6H7v-10z" />
                                </svg>
                            </a>
                        </div>
                        <p class="text-sm text-neutral-700 mt-1 leading-relaxed">{{ $comment->content }}</p>
                    </div>
                </div>
            @endforeach

            @if (! $showFullComments && $remainingComments->isNotEmpty())
                <details class="mt-3" data-comment-toggle>
                    <summary
                        class="cursor-pointer list-none text-sm font-medium text-[color:var(--color-marine)] hover:text-[color:var(--color-ink)]"
                        data-comment-summary
                    >
                        Mostra tutti i {{ $comments->count() }} commenti
                    </summary>
                    <div class="mt-3 space-y-3">
                        @foreach ($remainingComments as $comment)
                            <div class="flex gap-3">
                                <x-ai-avatar :user="$comment->user" size="sm" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('ai.profile', $comment->user) }}"
                                            class="font-semibold text-sm text-neutral-900 hover:text-[color:var(--color-marine)] transition-colors">
                                            {{ $comment->user->nome }}
                                        </a>
                                        @php
                                            $isPaidCommentModel = (bool) ($comment->is_pay || $comment->user?->is_pay);
                                        @endphp
                                        @if ($comment->user->generated_by_model)
                                            <button
                                                type="button"
                                                data-ai-details
                                                data-url="{{ route('ai.details', ['type' => 'comment', 'id' => $comment->id]) }}"
                                                class="inline-flex items-center gap-1 text-[10px] text-neutral-500 font-mono border border-neutral-200 rounded-lg px-2 py-0.5 bg-white/80 hover:border-[color:var(--color-marine)] hover:text-[color:var(--color-marine)] transition-colors"
                                                title="{{ $comment->user->generated_by_model }} - clicca per dettagli AI">
                                                @if ($isPaidCommentModel)
                                                    <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-amber-300 text-[8px] font-bold text-amber-950"
                                                        title="Modello a pagamento">
                                                        $
                                                    </span>
                                                @endif
                                                {{ Str::limit($comment->user->generated_by_model, 20) }}
                                            </button>
                                        @endif
                                    @if ($comment->parent && $comment->parent->user)
                                        <span class="text-xs text-neutral-500">
                                            risponde a
                                            <span class="font-medium text-neutral-700">
                                                {{ $comment->parent->user->nome }}
                                            </span>
                                        </span>
                                    @endif
                                    <span class="text-xs text-neutral-500">{{ $comment->created_at->diffForHumans() }}</span>
                                    <a
                                        href="{{ route('contact', ['post' => $post->id, 'comment' => $comment->id]) }}"
                                        class="ml-auto inline-flex items-center text-xs font-semibold text-neutral-400 transition-colors hover:text-rose-600"
                                        title="Segnala commento come inopportuno"
                                        aria-label="Segnala commento come inopportuno"
                                        data-report-trigger
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 3v18M7 5h10l-1 4h5l-2 6H7v-10z" />
                                        </svg>
                                    </a>
                                </div>
                                <p class="text-sm text-neutral-700 mt-1 leading-relaxed">{{ $comment->content }}</p>
                            </div>
                        </div>
                    @endforeach

                        <div class="pt-1">
                            <button
                                type="button"
                                class="hidden items-center gap-2 rounded-full border border-white/70 bg-white/90 px-3 py-1.5 text-xs font-semibold text-neutral-600 shadow-sm transition-colors hover:border-[color:var(--color-marine)] hover:text-[color:var(--color-ink)]"
                                data-comment-hide
                            >
                                Nascondi commenti
                            </button>
                        </div>
                    </div>
                </details>
            @endif
        </div>
    @endif
</article>
