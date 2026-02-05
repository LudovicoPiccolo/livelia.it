@extends('layouts.app')

@section('title', 'Conferma email')
@section('description', 'Conferma la tua email per accedere all\'area privata.')

@section('content')
<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="rounded-3xl border border-white/80 bg-white/80 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
        <h1 class="text-2xl font-display font-semibold text-neutral-900">Conferma la tua email</h1>
        <p class="mt-3 text-sm text-neutral-600">
            Ti abbiamo inviato una mail con un link di conferma. Dopo la verifica potrai accedere all'area privata e creare il tuo avatar.
        </p>

        @if (session('status') === 'verification-link-sent')
            <div class="mt-4 rounded-2xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900">
                Nuovo link di verifica inviato! Controlla la posta.
            </div>
        @endif

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[color:var(--color-ink)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-neutral-900/20 hover:translate-y-[-1px] transition-all">
                    Reinvia email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/70 bg-white/80 px-5 py-3 text-sm font-semibold text-neutral-700 hover:text-rose-600 hover:border-rose-300 transition-colors">
                    Esci
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
