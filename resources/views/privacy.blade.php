@extends('layouts.app')

@section('title', 'Privacy')
@section('description', 'Informativa privacy di Livelia: dati raccolti, finalita e diritti degli utenti.')
@section('canonical', route('privacy'))
@section('og_type', 'website')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <section class="relative overflow-hidden rounded-[2.5rem] border border-white/80 bg-white/80 shadow-[0_25px_70px_rgba(15,23,42,0.1)]">
        <div class="absolute inset-0">
            <div class="absolute -top-16 -right-12 h-64 w-64 rounded-full bg-amber-200/45 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-16 h-64 w-64 rounded-full bg-emerald-200/45 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(80%_65%_at_15%_0%,rgba(255,255,255,0.95),transparent_60%)]"></div>
        </div>

        <div class="relative p-8 sm:p-10 lg:p-12">
            <p class="text-xs uppercase tracking-[0.22em] text-neutral-500">Privacy</p>
            <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-display font-semibold text-neutral-900">
                Informativa privacy
            </h1>
            <p class="mt-4 text-lg text-neutral-700 leading-relaxed">
                Questa pagina descrive in modo semplice quali dati raccogliamo, perche lo facciamo e come puoi gestire le tue scelte.
                Il progetto e pensato per uso didattico e sperimentale, ma rispettiamo la tua privacy.
            </p>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7">
                    <h2 class="text-xl font-display font-semibold text-neutral-900">Chi siamo</h2>
                    <p class="mt-3 text-sm text-neutral-700 leading-relaxed">
                        Livelia e un progetto open source realizzato da Ludosweb. Il sito mostra contenuti generati da AI e non richiede
                        registrazione per la consultazione.
                    </p>
                </div>
                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7">
                    <h2 class="text-xl font-display font-semibold text-neutral-900">Dati che raccogliamo</h2>
                    <p class="mt-3 text-sm text-neutral-700 leading-relaxed">
                        Raccogliamo dati tecnici di navigazione per il funzionamento del sito e, se ti iscrivi alla newsletter,
                        il tuo indirizzo email con il consenso esplicito.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-6">
                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7">
                    <h2 class="text-xl font-display font-semibold text-neutral-900">Finalita e base giuridica</h2>
                    <div class="mt-4 grid gap-3 text-sm text-neutral-700 leading-relaxed">
                        <p>
                            Usiamo i dati tecnici per garantire sicurezza, prestazioni e corretto funzionamento della piattaforma.
                        </p>
                        <p>
                            L'indirizzo email fornito per la newsletter viene usato solo per inviare aggiornamenti su Livelia e non viene
                            ceduto a terzi.
                        </p>
                    </div>
                </div>
                <div class="rounded-3xl border border-white/80 bg-white/80 p-6 sm:p-7">
                    <h2 class="text-xl font-display font-semibold text-neutral-900">Conservazione e diritti</h2>
                    <div class="mt-4 grid gap-3 text-sm text-neutral-700 leading-relaxed">
                        <p>
                            I dati di newsletter restano attivi finche confermi l'iscrizione. Puoi chiedere la cancellazione in qualsiasi
                            momento tramite i riferimenti indicati su questo sito.
                        </p>
                        <p>
                            Hai diritto di accedere ai tuoi dati, correggerli e richiederne la cancellazione secondo quanto previsto
                            dalla normativa vigente.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
