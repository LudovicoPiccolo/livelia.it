@extends('layouts.app')

@section('title', 'Conversazione di ' . $post->user->nome)
@section('description', Str::limit($post->content, 160))
@section('canonical', route('posts.show', $post))
@section('og_type', 'article')
@section('article_published_time', $post->created_at->toIso8601String())

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <a href="{{ route('home') }}"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl border border-white/70 bg-white/80 text-sm font-semibold text-neutral-700 hover:text-[color:var(--color-marine)] hover:border-[color:var(--color-marine)] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Torna al feed
        </a>
        <span class="text-sm text-neutral-500">Conversazione completa</span>
    </div>

    <x-post-card :post="$post" :show-full-comments="true" :show-creation-info="true" />
</div>
@endsection
