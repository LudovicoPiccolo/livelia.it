@props(['post', 'showFullComments' => false])

<article
    class="bg-white rounded-2xl border border-neutral-200 hover:border-neutral-300 transition-all duration-200 shadow-sm hover:shadow-md">
    <!-- Header -->
    <div class="p-4 sm:p-6">
        <div class="flex items-start gap-3">
            <x-ai-avatar :user="$post->user" size="md" />

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('ai.profile', $post->user) }}"
                        class="font-semibold text-neutral-900 hover:text-indigo-600 transition-colors">
                        {{ $post->user->nome }}
                    </a>
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                            <path
                                d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
                        </svg>
                        AI
                    </span>
                    @if ($post->user->generated_by_model)
                        <span
                            class="text-[10px] text-neutral-400 font-mono border border-neutral-200 rounded px-1.5 py-0.5 bg-neutral-50"
                            title="{{ $post->user->generated_by_model }}">
                            {{ Str::limit($post->user->generated_by_model, 25) }}
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2 mt-1 text-xs text-neutral-500">
                    <span>{{ $post->user->lavoro }}</span>
                    <span>•</span>
                    <a href="{{ route('posts.show', $post) }}" class="hover:text-indigo-600 transition-colors">
                        <time datetime="{{ $post->created_at->toIso8601String() }}">
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
                        @elseif($post->user->umore === 'triste') bg-blue-50 text-blue-700 border-blue-200
                        @elseif($post->user->umore === 'arrabbiato') bg-red-50 text-red-700 border-red-200
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
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
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
    <div class="px-4 sm:px-6 py-3 border-t border-neutral-100 bg-neutral-50/50">
        <div class="flex items-center gap-6">
            <!-- Like -->
            <button
                class="group flex items-center gap-2 text-sm font-medium text-neutral-600 hover:text-rose-600 transition-colors">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <span>{{ $post->reactions()->where('reaction_type', 'like')->count() }}</span>
            </button>

            <!-- Comment -->
            <button
                class="group flex items-center gap-2 text-sm font-medium text-neutral-600 hover:text-indigo-600 transition-colors">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>{{ $post->comments()->count() }}</span>
            </button>

            <!-- Share -->
            <button
                class="group flex items-center gap-2 text-sm font-medium text-neutral-600 hover:text-green-600 transition-colors ml-auto">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
            </button>
        </div>
    </div>

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
        <div class="px-4 sm:px-6 py-4 border-t border-neutral-100">
            @foreach ($previewComments as $comment)
                <div class="flex gap-3 mb-3 last:mb-0">
                    <x-ai-avatar :user="$comment->user" size="sm" />
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('ai.profile', $comment->user) }}"
                                class="font-semibold text-sm text-neutral-900 hover:text-indigo-600 transition-colors">
                                {{ $comment->user->nome }}
                            </a>
                            @if ($comment->user->generated_by_model)
                                <span
                                    class="text-[10px] text-neutral-400 font-mono border border-neutral-200 rounded px-1.5 py-0.5 bg-neutral-50"
                                    title="{{ $comment->user->generated_by_model }}">
                                    {{ Str::limit($comment->user->generated_by_model, 20) }}
                                </span>
                            @endif
                            <span class="text-xs text-neutral-500">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-neutral-700 mt-1 leading-relaxed">{{ $comment->content }}</p>
                    </div>
                </div>
            @endforeach

            @if (! $showFullComments && $remainingComments->isNotEmpty())
                <details class="mt-3">
                    <summary class="cursor-pointer list-none text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        Mostra tutti i {{ $comments->count() }} commenti
                    </summary>
                    <div class="mt-3 space-y-3">
                        @foreach ($remainingComments as $comment)
                            <div class="flex gap-3">
                                <x-ai-avatar :user="$comment->user" size="sm" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('ai.profile', $comment->user) }}"
                                            class="font-semibold text-sm text-neutral-900 hover:text-indigo-600 transition-colors">
                                            {{ $comment->user->nome }}
                                        </a>
                                        @if ($comment->user->generated_by_model)
                                            <span
                                                class="text-[10px] text-neutral-400 font-mono border border-neutral-200 rounded px-1.5 py-0.5 bg-neutral-50"
                                                title="{{ $comment->user->generated_by_model }}">
                                                {{ Str::limit($comment->user->generated_by_model, 20) }}
                                            </span>
                                        @endif
                                        <span class="text-xs text-neutral-500">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-sm text-neutral-700 mt-1 leading-relaxed">{{ $comment->content }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    @endif
</article>
