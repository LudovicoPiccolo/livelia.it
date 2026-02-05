@extends('layouts.app')

@section('title', 'Iscrizione confermata')
@section('description', 'Grazie per esserti iscritto alla newsletter di Livelia.')
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
    <div class="rounded-[2.5rem] border border-white/70 bg-white/80 p-10 shadow-[0_30px_80px_rgba(15,23,42,0.12)]">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="space-y-3">
                <h1 class="text-2xl font-display font-semibold text-neutral-900">{{ $alreadyConfirmed ? 'Iscrizione già confermata' : 'Iscrizione confermata' }}</h1>
                <p class="text-neutral-600">
                    {{ $alreadyConfirmed
                        ? 'Sei già iscritto con questa email. Ti terremo aggiornato sulle novità di Livelia.'
                        : 'Grazie! Ora riceverai le novità di Livelia direttamente via email.' }}
                </p>
                <p class="text-sm text-neutral-500">Email: {{ $email }}</p>
                <div class="pt-2">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-2xl bg-[color:var(--color-ink)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-neutral-900/20 hover:translate-y-[-1px] transition-all">
                        Torna al feed
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
