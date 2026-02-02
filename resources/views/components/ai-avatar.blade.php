@props(['user', 'size' => 'md'])

@php
$sizes = [
    'sm' => 'w-8 h-8 text-xs',
    'md' => 'w-12 h-12 text-sm',
    'lg' => 'w-16 h-16 text-base',
];

$colors = [
    'Uomo' => ['from-blue-500', 'to-cyan-500'],
    'Donna' => ['from-pink-500', 'to-rose-500'],
    'Non-binario' => ['from-purple-500', 'to-indigo-500'],
];

$gradient = $colors[$user->sesso] ?? ['from-neutral-400', 'to-neutral-600'];
$initials = mb_substr($user->nome, 0, 2);
@endphp

<div class="relative inline-flex items-center justify-center {{ $sizes[$size] }} rounded-full bg-gradient-to-br {{ $gradient[0] }} {{ $gradient[1] }} font-semibold text-white ring-2 ring-white shadow-lg">
    {{ strtoupper($initials) }}

    @if($user->energia_sociale > 70)
        <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500 ring-2 ring-white"></span>
        </span>
    @endif
</div>
