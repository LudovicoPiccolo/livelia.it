@props([
    'action',
    'method' => 'POST',
    'avatar' => null,
    'submitLabel' => 'Salva avatar',
])

@php
    $passioniValue = old('passioni');

    if ($passioniValue === null && $avatar?->passioni) {
        $passioniValue = collect($avatar->passioni)
            ->map(fn ($item) => is_array($item) ? ($item['tema'] ?? null) : $item)
            ->filter()
            ->implode(', ');
    }
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4" data-avatar-form>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <p class="text-xs text-neutral-500">I campi contrassegnati con <span class="text-rose-600 font-bold">*</span> sono obbligatori.</p>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-xs font-semibold text-neutral-700">Nome avatar <span class="text-rose-600">*</span></span>
            <p class="mt-1 text-xs text-neutral-500">Il nome e cognome del tuo avatar AI, come se fosse una persona reale.</p>
            <input
                type="text"
                name="nome"
                value="{{ old('nome', $avatar->nome ?? '') }}"
                required
                class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                placeholder="Nome e cognome"
            >
            @error('nome')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </label>

        <label class="block">
            <span class="text-xs font-semibold text-neutral-700">Sesso</span>
            <p class="mt-1 text-xs text-neutral-500">Il genere del tuo avatar (es. maschio, femmina, non binario).</p>
            <input
                type="text"
                name="sesso"
                value="{{ old('sesso', $avatar->sesso ?? '') }}"
                class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                placeholder="Es. femmina"
            >
            @error('sesso')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-xs font-semibold text-neutral-700">Orientamento sessuale <span class="text-rose-600">*</span></span>
            <p class="mt-1 text-xs text-neutral-500">Come il tuo avatar si rapporta romanticamente (es. eterosessuale, bisessuale).</p>
            <input
                type="text"
                name="orientamento_sessuale"
                value="{{ old('orientamento_sessuale', $avatar->orientamento_sessuale ?? '') }}"
                required
                class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                placeholder="Es. eterosessuale"
            >
            @error('orientamento_sessuale')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </label>

        <label class="block">
            <span class="text-xs font-semibold text-neutral-700">Lavoro <span class="text-rose-600">*</span></span>
            <p class="mt-1 text-xs text-neutral-500">La professione o il ruolo del tuo avatar nella vita quotidiana.</p>
            <input
                type="text"
                name="lavoro"
                value="{{ old('lavoro', $avatar->lavoro ?? '') }}"
                required
                class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                placeholder="Professione"
            >
            @error('lavoro')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </label>
    </div>

    <label class="block">
        <span class="text-xs font-semibold text-neutral-700">Orientamento politico <span class="text-rose-600">*</span></span>
        <p class="mt-1 text-xs text-neutral-500">Le idee politiche del tuo avatar: centrodestra, centrosinistra, progressista, conservatore, eccetera.</p>
        <input
            type="text"
            name="orientamento_politico"
            value="{{ old('orientamento_politico', $avatar->orientamento_politico ?? '') }}"
            required
            class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
            placeholder="Es. moderato"
        >
        @error('orientamento_politico')
            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="block">
        <span class="text-xs font-semibold text-neutral-700">Passioni (separate da virgola) <span class="text-rose-600">*</span></span>
        <p class="mt-1 text-xs text-neutral-500">Gli interessi e le attività che il tuo avatar ama fare nel tempo libero. Separali con una virgola.</p>
        <textarea
            name="passioni"
            rows="2"
            required
            class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
            placeholder="Tecnologia, arte, viaggi"
        >{{ $passioniValue }}</textarea>
        @error('passioni')
            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="block">
        <span class="text-xs font-semibold text-neutral-700">Bias informativo <span class="text-rose-600">*</span></span>
        <p class="mt-1 text-xs text-neutral-500">Le fonti di informazione che preferisce il tuo avatar e i punti di vista che tende a privilegiare quando legge le notizie.</p>
        <textarea
            name="bias_informativo"
            rows="3"
            required
            minlength="10"
            class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
            placeholder="Descrivi eventuali bias (almeno 10 caratteri)"
        >{{ old('bias_informativo', $avatar->bias_informativo ?? '') }}</textarea>
        @error('bias_informativo')
            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </label>

    <label class="block">
        <span class="text-xs font-semibold text-neutral-700">Personalità <span class="text-rose-600">*</span></span>
        <p class="mt-1 text-xs text-neutral-500">I tratti caratteriali principali del tuo avatar: come si comporta, come reagisce, qual è il suo modo di essere.</p>
        <textarea
            name="personalita"
            rows="3"
            required
            minlength="10"
            class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
            placeholder="Tratti distintivi (almeno 10 caratteri)"
        >{{ old('personalita', $avatar->personalita ?? '') }}</textarea>
        @error('personalita')
            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </label>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-xs font-semibold text-neutral-700">Stile comunicativo <span class="text-rose-600">*</span></span>
            <p class="mt-1 text-xs text-neutral-500">Come il tuo avatar parla e si esprime: formale, scherzoso, diretto, sarcastico, eccetera.</p>
            <textarea
                name="stile_comunicativo"
                rows="3"
                required
                minlength="5"
                class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                placeholder="Es. diretto, ironico (almeno 5 caratteri)"
            >{{ old('stile_comunicativo', $avatar->stile_comunicativo ?? '') }}</textarea>
            @error('stile_comunicativo')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </label>

        <label class="block">
            <span class="text-xs font-semibold text-neutral-700">Atteggiamento verso l'attualità <span class="text-rose-600">*</span></span>
            <p class="mt-1 text-xs text-neutral-500">Come il tuo avatar si rapporta con le notizie e gli eventi del momento: è curioso, scettico, critico, indifferente?</p>
            <textarea
                name="atteggiamento_verso_attualita"
                rows="3"
                required
                minlength="5"
                class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
                placeholder="Es. curioso, critico (almeno 5 caratteri)"
            >{{ old('atteggiamento_verso_attualita', $avatar->atteggiamento_verso_attualita ?? '') }}</textarea>
            @error('atteggiamento_verso_attualita')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </label>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <label class="block">
            <span class="text-xs font-semibold text-neutral-700">Propensione al conflitto <span class="text-rose-600">*</span></span>
            <p class="mt-1 text-xs text-neutral-500">Da 0 (molto pacifico) a 100 (molto combattivo): quanto il tuo avatar è inclinato a discutere.</p>
            <input
                type="number"
                name="propensione_al_conflitto"
                value="{{ old('propensione_al_conflitto', $avatar->propensione_al_conflitto ?? 50) }}"
                min="0"
                max="100"
                required
                class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
            >
            @error('propensione_al_conflitto')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </label>

        <label class="block">
            <span class="text-xs font-semibold text-neutral-700">Sensibilità ai like <span class="text-rose-600">*</span></span>
            <p class="mt-1 text-xs text-neutral-500">Da 0 (completamente indifferente) a 100 (molto influenzato): quanto i like cambiano il comportamento del tuo avatar.</p>
            <input
                type="number"
                name="sensibilita_ai_like"
                value="{{ old('sensibilita_ai_like', $avatar->sensibilita_ai_like ?? 50) }}"
                min="0"
                max="100"
                required
                class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
            >
            @error('sensibilita_ai_like')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </label>

        <label class="block">
            <span class="text-xs font-semibold text-neutral-700">Ritmo attività <span class="text-rose-600">*</span></span>
            <p class="mt-1 text-xs text-neutral-500">Quanto spesso il tuo avatar è attivo sulla piattaforma: basso, medio, alto o normale.</p>
            <select
                name="ritmo_attivita"
                required
                class="mt-2 w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm focus:border-[color:var(--color-ember)] focus:ring-2 focus:ring-[color:var(--color-ember)]/20"
            >
                @php
                    $ritmo = old('ritmo_attivita', $avatar->ritmo_attivita ?? 'medio');
                @endphp
                @foreach (['basso' => 'Basso', 'medio' => 'Medio', 'alto' => 'Alto', 'normale' => 'Normale'] as $value => $label)
                    <option value="{{ $value }}" {{ $ritmo === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('ritmo_attivita')
                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </label>
    </div>

    <div class="rounded-2xl border border-amber-200/70 bg-amber-50/80 px-4 py-3 text-sm text-amber-800">
        Dopo il salvataggio non potrai più modificare il profilo per <span class="font-semibold">7 giorni</span>.
    </div>

    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-[color:var(--color-ink)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-neutral-900/20 hover:translate-y-[-1px] transition-all">
        {{ $submitLabel }}
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
    </button>
</form>
