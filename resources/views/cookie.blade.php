@extends('layouts.app')

@section('title', 'Cookie')
@section('description', 'Cookie policy di Livelia: tipologie di cookie e gestione delle preferenze.')
@section('canonical', route('cookie'))
@section('og_type', 'website')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <section class="relative overflow-hidden rounded-[2.5rem] border border-white/80 bg-white/80 shadow-[0_25px_70px_rgba(15,23,42,0.1)]">
        <div class="absolute inset-0">
            <div class="absolute -top-20 -left-12 h-64 w-64 rounded-full bg-sky-200/45 blur-3xl"></div>
            <div class="absolute -bottom-20 -right-12 h-64 w-64 rounded-full bg-amber-200/45 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(80%_65%_at_15%_0%,rgba(255,255,255,0.95),transparent_60%)]"></div>
        </div>

        <div class="relative p-8 sm:p-10 lg:p-12">
            <p class="text-xs uppercase tracking-[0.22em] text-neutral-500">Cookie</p>
            <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-display font-semibold text-neutral-900">
                Cookie policy
            </h1>
            <p class="mt-4 text-lg text-neutral-700 leading-relaxed">
                Qui trovi una spiegazione chiara su cosa sono i cookie, quali usiamo e come puoi gestire le tue preferenze.
            </p>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7">
                    <h2 class="text-xl font-display font-semibold text-neutral-900">Cosa sono i cookie</h2>
                    <p class="mt-3 text-sm text-neutral-700 leading-relaxed">
                        I cookie sono piccoli file salvati nel browser che aiutano il sito a funzionare correttamente e a migliorare
                        l'esperienza di navigazione.
                    </p>
                </div>
                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7">
                    <h2 class="text-xl font-display font-semibold text-neutral-900">Categorie utilizzate</h2>
                    <div class="mt-3 grid gap-3 text-sm text-neutral-700 leading-relaxed">
                        <p>
                            Cookie tecnici: necessari per il funzionamento di base del sito.
                        </p>
                        <p>
                            Cookie di misurazione: usati per capire in modo aggregato come viene utilizzata la piattaforma e migliorare
                            i contenuti.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-6">
                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7">
                    <h2 class="text-xl font-display font-semibold text-neutral-900">Gestione preferenze</h2>
                    <p class="mt-3 text-sm text-neutral-700 leading-relaxed">
                        Al primo accesso puoi scegliere se accettare o rifiutare i cookie di misurazione tramite il banner in basso.
                        Puoi cambiare idea in qualsiasi momento cancellando i cookie dal tuo browser.
                    </p>
                </div>
                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7">
                    <h2 class="text-xl font-display font-semibold text-neutral-900">Aggiornamenti</h2>
                    <p class="mt-3 text-sm text-neutral-700 leading-relaxed">
                        Questa cookie policy puo essere aggiornata nel tempo per adeguarsi a nuove funzionalita o strumenti di analisi.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
