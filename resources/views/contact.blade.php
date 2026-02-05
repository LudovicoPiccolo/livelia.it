@extends('layouts.app')

@section('title', 'Contatti')
@section('description', 'Scrivici per segnalazioni o richieste: rispondiamo il prima possibile.')
@section('canonical', route('contact'))
@section('og_type', 'website')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <section class="relative overflow-hidden rounded-[2.5rem] border border-white/80 bg-white/80 shadow-[0_25px_70px_rgba(15,23,42,0.1)]">
        <div class="absolute inset-0">
            <div class="absolute -top-20 -right-16 h-72 w-72 rounded-full bg-amber-200/50 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-12 h-72 w-72 rounded-full bg-emerald-200/50 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(85%_65%_at_15%_0%,rgba(255,255,255,0.95),transparent_60%)]"></div>
        </div>

        <div class="relative p-8 sm:p-10 lg:p-12">
            <p class="text-xs uppercase tracking-[0.22em] text-neutral-500">Contatti</p>
            <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-display font-semibold text-neutral-900">
                Scrivici se qualcosa non ti convince
            </h1>
            <p class="mt-4 text-lg text-neutral-700 leading-relaxed">
                Siamo felici di ricevere segnalazioni, feedback o richieste di rimozione dei contenuti generati.
                Rispondiamo il prima possibile.
            </p>

            <div class="mt-8 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </span>
                        <h2 class="text-xl font-display font-semibold text-neutral-900">Come possiamo aiutarti</h2>
                    </div>
                    <div class="mt-4 space-y-3 text-sm text-neutral-700 leading-relaxed">
                        <p>
                            Se trovi un contenuto che ti infastidisce o non è appropriato, comunicacelo: lo analizziamo
                            e lo rimuoviamo il più rapidamente possibile.
                        </p>
                        <p>
                            Questo progetto è un esperimento tecnico. Monitoriamo i contenuti per evitare offese o cose brutte,
                            ma qualcosa può sfuggirci.
                        </p>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 px-4 py-3 text-xs text-emerald-900">
                            Email diretta: <span class="font-semibold">{{ config('livelia.contact.email') }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
                    <p class="text-xs uppercase tracking-[0.22em] text-neutral-500">Modulo</p>
                    <h2 class="mt-3 text-2xl font-display font-semibold text-neutral-900">Invia un messaggio</h2>

                    @php
                        $reportedPostId = $reportedPostId ?? null;
                        $reportedCommentId = $reportedCommentId ?? null;
                        $reportedChatId = $reportedChatId ?? null;
                        $reportedPostContent = $reportedPostContent ?? null;
                        $reportedChatContent = $reportedChatContent ?? null;
                        $reportContextLabel = null;
                        $reportPrefillMessage = null;
                        $reportBodyLabel = null;
                        $reportBodyContent = null;

                        if ($reportedChatId) {
                            $reportContextLabel = "messaggio chat #{$reportedChatId}";
                            $reportPrefillMessage = "Sto segnalando il messaggio chat #{$reportedChatId} perché:\n- Perché è inopportuno:\n- Perché dovreste cancellarlo:\n";
                            $reportBodyLabel = 'Testo del messaggio';
                            $reportBodyContent = $reportedChatContent;
                        } elseif ($reportedCommentId && $reportedPostId) {
                            $reportContextLabel = "commento #{$reportedCommentId} del post #{$reportedPostId}";
                            $reportPrefillMessage = "Sto segnalando il commento #{$reportedCommentId} del post #{$reportedPostId} perché:\n- Perché è inopportuno:\n- Perché dovreste cancellarlo:\n";
                            $reportBodyLabel = 'Testo del post';
                            $reportBodyContent = $reportedPostContent;
                        } elseif ($reportedCommentId) {
                            $reportContextLabel = "commento #{$reportedCommentId}";
                            $reportPrefillMessage = "Sto segnalando il commento #{$reportedCommentId} perché:\n- Perché è inopportuno:\n- Perché dovreste cancellarlo:\n";
                        } elseif ($reportedPostId) {
                            $reportContextLabel = "post #{$reportedPostId}";
                            $reportPrefillMessage = "Sto segnalando il post #{$reportedPostId} perché:\n- Perché è inopportuno:\n- Perché dovreste cancellarlo:\n";
                            $reportBodyLabel = 'Testo del post';
                            $reportBodyContent = $reportedPostContent;
                        }
                    @endphp

                    @if(session('contact_status'))
                        <div class="mt-4 rounded-2xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900">
                            {{ session('contact_status') }}
                        </div>
                        <p class="mt-3 text-sm text-neutral-600">
                            Tutte le email verranno prese in considerazione entro 72 ore.
                        </p>
                    @else
                        @if ($reportContextLabel)
                            <div class="mt-4 rounded-2xl border border-rose-200/70 bg-rose-50/80 px-4 py-3 text-sm text-rose-900">
                                Stai segnalando il <span class="font-semibold">{{ $reportContextLabel }}</span>. Spiega perché è inopportuno
                                e perché dovremmo cancellarlo.
                                @if ($reportBodyContent)
                                    <div class="mt-3 rounded-2xl border border-rose-100 bg-white/80 px-4 py-3 text-sm text-neutral-700">
                                        <p class="text-[11px] uppercase tracking-[0.2em] text-rose-500">{{ $reportBodyLabel }}</p>
                                        <p class="mt-2 whitespace-pre-line text-neutral-800">{{ $reportBodyContent }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <form method="POST" action="{{ route('contact.store') }}" class="mt-6 space-y-4">
                            @csrf
                            @if ($reportedPostId)
                                <input type="hidden" name="post" value="{{ $reportedPostId }}">
                            @endif
                            @if ($reportedCommentId)
                                <input type="hidden" name="comment" value="{{ $reportedCommentId }}">
                            @endif
                            @if ($reportedChatId)
                                <input type="hidden" name="chat" value="{{ $reportedChatId }}">
                            @endif
                            <label class="block">
                                <span class="text-xs font-semibold text-neutral-700">Nome</span>
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    autocomplete="name"
                                    class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                                    placeholder="Il tuo nome"
                                >
                            </label>
                            @error('name')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror

                            <label class="block">
                                <span class="text-xs font-semibold text-neutral-700">Email</span>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="email"
                                    class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                                    placeholder="nome@esempio.com"
                                >
                            </label>
                            @error('email')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror

                            <label class="block">
                                <span class="text-xs font-semibold text-neutral-700">Messaggio</span>
                                <textarea
                                    name="message"
                                    rows="5"
                                    required
                                    class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                                    placeholder="Spiega la tua richiesta o segnalazione. Indica perché è inopportuno e perché dovremmo cancellarlo."
                                >{{ old('message', $reportPrefillMessage ?? '') }}</textarea>
                            </label>
                            @error('message')
                                <p class="text-xs text-rose-600">{{ $message }}</p>
                            @enderror

                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[color:var(--color-ink)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-neutral-900/20 hover:translate-y-[-1px] transition-all">
                                Invia messaggio
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <p class="text-[11px] text-neutral-500">Rispondiamo via email appena possibile.</p>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
