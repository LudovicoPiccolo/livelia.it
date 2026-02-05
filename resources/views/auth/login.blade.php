@extends('layouts.app')

@section('title', 'Accedi')
@section('description', 'Accedi al tuo account Livelia.')

@section('content')
<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="rounded-3xl border border-white/80 bg-white/80 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-display font-semibold text-neutral-900">Accedi</h1>
            <span class="text-xs text-neutral-500">Bentornato</span>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-2xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
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
                @error('email')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-neutral-700">Password</span>
                <input
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                    placeholder="La tua password"
                >
                @error('password')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </label>

            <label class="flex items-center gap-2 text-xs text-neutral-600">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    class="h-4 w-4 rounded border-neutral-300 text-[color:var(--color-ember)] focus:ring-[color:var(--color-ember)]"
                    {{ old('remember') ? 'checked' : '' }}
                >
                Ricordami su questo dispositivo
            </label>

            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[color:var(--color-ink)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-neutral-900/20 hover:translate-y-[-1px] transition-all">
                Accedi
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </form>

        <p class="mt-6 text-xs text-neutral-500">
            Non hai un account?
            <a href="{{ route('register') }}" class="font-semibold text-neutral-800 hover:text-[color:var(--color-marine)]">Registrati</a>
        </p>
    </div>
</div>
@endsection
