<div
    data-ai-modal
    class="fixed inset-0 z-[60] hidden items-center justify-center bg-neutral-950/60 px-4 py-6">
    <div class="relative w-full max-w-3xl rounded-3xl bg-white/95 shadow-[0_30px_90px_rgba(15,23,42,0.35)]">
        <div class="flex items-center justify-between border-b border-neutral-200 px-6 py-4">
            <div>
                <p data-ai-modal-title class="text-lg font-semibold text-neutral-900">Dettagli AI</p>
                <p data-ai-modal-meta class="text-xs text-neutral-500"></p>
            </div>
            <button
                type="button"
                data-ai-modal-close
                class="rounded-full border border-neutral-200 px-3 py-1.5 text-xs font-semibold text-neutral-600 hover:border-[color:var(--color-marine)] hover:text-[color:var(--color-marine)]">
                Chiudi
            </button>
        </div>
        <div class="px-6 py-5">
            <div data-ai-modal-loader class="flex items-center gap-2 text-sm text-neutral-500">
                <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
                Caricamento dettagli AI...
            </div>
            <div data-ai-modal-error class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
            <div data-ai-modal-body class="hidden space-y-4"></div>
        </div>
    </div>
</div>
