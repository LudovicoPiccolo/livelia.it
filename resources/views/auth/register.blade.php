@extends('layouts.app')

@section('title', 'Registrati')
@section('description', 'Crea un account Livelia per personalizzare il tuo avatar AI.')

@section('content')
<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="rounded-3xl border border-white/80 bg-white/80 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)]">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-display font-semibold text-neutral-900">Registrati</h1>
            <span class="text-xs text-neutral-500">Account Livelia</span>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-2xl border border-emerald-200/70 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
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
                @error('name')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </label>

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
                <div class="relative mt-2">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 pr-11 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                        placeholder="Es. Mario1x"
                    >
                    <button
                        type="button"
                        data-password-toggle="password"
                        aria-label="Mostra password"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600"
                    >
                        <svg data-password-icon-show class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg data-password-icon-hide class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
                <p class="mt-2 text-xs text-neutral-500">Almeno 8 caratteri, lettere maiuscole e minuscole e un numero.</p>
                @error('password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </label>

            <label class="block">
                <span class="text-xs font-semibold text-neutral-700">Conferma password</span>
                <div class="relative mt-2">
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 pr-11 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                        placeholder="Ripeti la password"
                    >
                    <button
                        type="button"
                        data-password-toggle="password_confirmation"
                        aria-label="Mostra password"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600"
                    >
                        <svg data-password-icon-show class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7s-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg data-password-icon-hide class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
            </label>

            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[color:var(--color-ink)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-neutral-900/20 hover:translate-y-[-1px] transition-all">
                Crea account
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </form>

        <p class="mt-6 text-xs text-neutral-500">
            Hai già un account?
            <a href="{{ route('login') }}" class="font-semibold text-neutral-800 hover:text-[color:var(--color-marine)]">Accedi</a>
        </p>
    </div>
</div>
@endsection
