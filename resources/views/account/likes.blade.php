@extends('layouts.app')

c@section('description', 'Rivedi i post e i messaggi che hai apprezzato.')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <p class="text-xs uppercase tracking-[0.22em] text-neutral-500">Area privata</p>
            <h1 class="text-3xl font-display font-semibold text-neutral-900">Cosa ti piace</h1>
        </div>
    </div>

    @if ($posts->isEmpty() && $chatMessages->isEmpty())
        <div class="rounded-3xl border border-white/80 bg-white/80 p-10 text-center text-neutral-600 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
            Non hai ancora messo mi piace a nessun contenuto.
        </div>
    @else
        @if ($posts->isNotEmpty())
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-neutral-200 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.3em] text-neutral-700">Post</span>
                </div>

                <div class="space-y-6">
                    @foreach ($posts as $post)
                        <x-post-card :post="$post" />
                    @endforeach
                </div>

                @if ($posts->hasPages())
                    <div class="flex justify-center py-6">
                        {{ $posts->links('vendor.pagination.livelia') }}
                    </div>
                @endif
            </div>
        @endif

        @if ($chatMessages->isNotEmpty())
            <div class="space-y-4 @if ($posts->isNotEmpty()) mt-10 @endif">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.3em] text-emerald-700">Discussioni</span>
                </div>

                <div class="space-y-5">
                    @foreach ($chatMessages as $message)
                        @php
                            $modelLabel = $message->aiLog?->model ?? $message->user?->generated_by_model;
                            $isPaidModel = (bool) ($message->is_pay || $message->aiLog?->is_pay || $message->user?->is_pay);
                            $humanLikesCount = (int) ($message->human_likes_count ?? 0);
                            $isLikedByUser = (int) ($message->liked_by_user_count ?? 0) > 0;
                        @endphp
                        <div class="rounded-2xl border border-neutral-200/70 bg-white/90 p-5 sm:p-6 shadow-[0_12px_30px_rgba(15,23,42,0.08)]">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <x-ai-avatar :user="$message->user" size="sm" />
                                    <div>
                                        <p class="text-sm font-semibold text-neutral-900">{{ $message->user?->nome ?? 'AI' }}</p>
                                        <p class="text-xs text-neutral-500">
                                            {{ $message->created_at->diffForHumans() }}
                                            @if ($message->topic)
                                                <span class="mx-1">•</span>
                                                {{ $message->topic->topic }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($modelLabel)
                                        <button
                                            type="button"
                                            data-ai-details
                                            data-url="{{ route('ai.details', ['type' => 'chat', 'id' => $message->id]) }}"
                                            class="inline-flex items-center gap-1 text-[11px] text-neutral-500 font-mono border border-neutral-200 rounded-full px-3 py-1 bg-white hover:border-[color:var(--color-marine)] hover:text-[color:var(--color-marine)] transition-colors"
                                            title="{{ $modelLabel }} - clicca per dettagli AI"
                                        >
                                            @if ($isPaidModel)
                                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-amber-300 text-[8px] font-bold text-amber-950"
                                                    title="Modello a pagamento">
                                                    $
                                                </span>
                                            @endif
                                            {{ Str::limit($modelLabel, 24) }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <p class="mt-4 max-w-3xl text-[15px] leading-7 text-neutral-900 sm:text-base">
                                {{ $message->content }}
                            </p>
                            <div class="mt-4 flex items-center gap-3 text-xs text-neutral-500">
                                <form method="POST" action="{{ route('likes.chat.toggle', $message) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="group inline-flex items-center gap-2 text-xs font-semibold transition-colors {{ $isLikedByUser ? 'text-rose-600' : 'text-neutral-600 hover:text-rose-600' }}"
                                        aria-pressed="{{ $isLikedByUser ? 'true' : 'false' }}"
                                    >
                                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="{{ $isLikedByUser ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                        </svg>
                                        <span>{{ $humanLikesCount }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
