@extends('layouts.app')

@section('title', 'News')
@section('description', 'Aggiornamenti ufficiali di Livelia: versioni, cambiamenti e note di rilascio.')
@section('canonical', route('news'))
@section('og_type', 'website')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <section class="relative overflow-hidden rounded-[2.5rem] border border-white/80 bg-white/80 shadow-[0_25px_70px_rgba(15,23,42,0.1)]">
        <div class="absolute inset-0">
            <div class="absolute -top-16 -right-12 h-72 w-72 rounded-full bg-sky-200/50 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-12 h-72 w-72 rounded-full bg-amber-200/50 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(85%_65%_at_15%_0%,rgba(255,255,255,0.95),transparent_60%)]"></div>
        </div>

        <div class="relative p-8 sm:p-10 lg:p-12">
            <p class="text-xs uppercase tracking-[0.22em] text-neutral-500">News</p>
            <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-display font-semibold text-neutral-900">
                Cambiamenti e versioni di Livelia
            </h1>
            <p class="mt-4 text-lg text-neutral-700 leading-relaxed">
                Qui trovi gli aggiornamenti principali del progetto: cosa cambia, perche e quando. Un riepilogo chiaro e
                accessibile per chi vuole restare aggiornato.
            </p>
        </div>
    </section>

    <section class="mt-10 grid gap-6">
        @forelse ($newsItems as $item)
            <article class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.22em] text-neutral-500">Versione {{ $item->version }}</p>
                        <h2 class="mt-2 text-2xl font-display font-semibold text-neutral-900">
                            {{ $item->title }}
                        </h2>
                        <p class="mt-3 text-sm text-neutral-600 leading-relaxed">
                            {{ $item->summary }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-neutral-600">
                        <span class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1">
                            <svg class="h-4 w-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ $item->date?->format('Y-m-d') }}
                        </span>
                    </div>
                </div>

                @if (! empty($item->details) && is_array($item->details))
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ($item->details as $detail)
                            <div class="flex items-start gap-3 rounded-2xl border border-neutral-200 bg-neutral-50/80 p-4">
                                <span class="mt-1 h-2.5 w-2.5 rounded-full bg-[color:var(--color-marine)]"></span>
                                <p class="text-xs text-neutral-700 leading-relaxed">{{ $detail }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-3xl border border-white/80 bg-white/80 p-10 text-center shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-neutral-900">Nessuna news pubblicata</h3>
                <p class="mt-2 text-sm text-neutral-600">Quando pubblicheremo un aggiornamento, apparira qui.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
