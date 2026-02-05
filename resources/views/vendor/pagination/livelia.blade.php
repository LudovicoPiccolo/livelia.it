@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigazione paginazione" data-pagination="livelia" class="w-full max-w-3xl animate-[fade-up_0.35s_ease-out]">
        <div class="flex items-center justify-between gap-3 rounded-2xl border border-white/70 bg-white/70 px-3 py-2 shadow-[0_18px_36px_rgba(15,23,42,0.12)] backdrop-blur sm:hidden">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Precedente" class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/50 px-4 py-2 text-sm font-semibold text-neutral-400 shadow-[0_8px_18px_rgba(15,23,42,0.08)]">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Precedente
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/80 px-4 py-2 text-sm font-semibold text-neutral-700 shadow-[0_8px_18px_rgba(15,23,42,0.12)] transition-all duration-200 ease-out hover:-translate-y-0.5 hover:text-[color:var(--color-marine)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-marine)]/30 focus-visible:ring-offset-2">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Precedente
                </a>
            @endif

            <span class="text-[11px] font-semibold text-neutral-500">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/80 px-4 py-2 text-sm font-semibold text-neutral-700 shadow-[0_8px_18px_rgba(15,23,42,0.12)] transition-all duration-200 ease-out hover:-translate-y-0.5 hover:text-[color:var(--color-marine)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-marine)]/30 focus-visible:ring-offset-2">
                    Successivo
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Successivo" class="inline-flex items-center gap-2 rounded-full border border-white/70 bg-white/50 px-4 py-2 text-sm font-semibold text-neutral-400 shadow-[0_8px_18px_rgba(15,23,42,0.08)]">
                    Successivo
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>

        <div class="hidden sm:flex sm:items-center sm:justify-between sm:gap-4 sm:rounded-2xl sm:border sm:border-white/70 sm:bg-white/70 sm:px-4 sm:py-3 sm:shadow-[0_18px_36px_rgba(15,23,42,0.12)] sm:backdrop-blur">
            <p class="text-xs font-medium text-neutral-500">
                Risultati
                @if ($paginator->firstItem())
                    <span class="font-semibold text-neutral-700">{{ $paginator->firstItem() }}</span>
                    –
                    <span class="font-semibold text-neutral-700">{{ $paginator->lastItem() }}</span>
                @else
                    <span class="font-semibold text-neutral-700">{{ $paginator->count() }}</span>
                @endif
                di
                <span class="font-semibold text-neutral-700">{{ $paginator->total() }}</span>
            </p>

            <div class="inline-flex items-center gap-1 rounded-full border border-white/80 bg-white/80 px-2 py-2 shadow-[0_12px_30px_rgba(15,23,42,0.12)] backdrop-blur">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Precedente" class="inline-flex items-center justify-center rounded-full border border-white/70 bg-white/50 px-3 py-2 text-sm font-semibold text-neutral-400 shadow-[0_6px_16px_rgba(15,23,42,0.08)]">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white/80 px-3 py-2 text-sm font-semibold text-neutral-700 shadow-[0_6px_16px_rgba(15,23,42,0.12)] transition-all duration-200 ease-out hover:-translate-y-0.5 hover:text-[color:var(--color-marine)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-marine)]/30 focus-visible:ring-offset-2">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true" class="inline-flex items-center justify-center rounded-full border border-white/70 bg-white/60 px-3 py-2 text-sm font-semibold text-neutral-500">
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex min-w-[2.25rem] items-center justify-center rounded-full bg-[var(--color-marine)] px-3 py-2 text-sm font-semibold text-white shadow-[0_10px_25px_rgba(15,118,110,0.35)] ring-2 ring-white/80">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="inline-flex min-w-[2.25rem] items-center justify-center rounded-full border border-white/80 bg-white/80 px-3 py-2 text-sm font-semibold text-neutral-700 shadow-[0_6px_16px_rgba(15,23,42,0.12)] transition-all duration-200 ease-out hover:-translate-y-0.5 hover:border-[color:var(--color-marine)]/40 hover:text-[color:var(--color-marine)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-marine)]/30 focus-visible:ring-offset-2" aria-label="Vai alla pagina {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white/80 px-3 py-2 text-sm font-semibold text-neutral-700 shadow-[0_6px_16px_rgba(15,23,42,0.12)] transition-all duration-200 ease-out hover:-translate-y-0.5 hover:text-[color:var(--color-marine)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--color-marine)]/30 focus-visible:ring-offset-2" aria-label="Successivo">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Successivo" class="inline-flex items-center justify-center rounded-full border border-white/70 bg-white/50 px-3 py-2 text-sm font-semibold text-neutral-400 shadow-[0_6px_16px_rgba(15,23,42,0.08)]">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
