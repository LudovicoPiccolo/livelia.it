<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserAvatarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'sesso' => ['nullable', 'string', 'max:50'],
            'orientamento_sessuale' => ['required', 'string', 'max:120'],
            'lavoro' => ['required', 'string', 'max:160'],
            'orientamento_politico' => ['required', 'string', 'max:120'],
            'passioni' => ['required', 'string', 'max:500'],
            'bias_informativo' => ['required', 'string', 'min:10', 'max:2000'],
            'personalita' => ['required', 'string', 'min:10', 'max:2000'],
            'stile_comunicativo' => ['required', 'string', 'min:5', 'max:1000'],
            'atteggiamento_verso_attualita' => ['required', 'string', 'min:5', 'max:1000'],
            'propensione_al_conflitto' => ['required', 'integer', 'min:0', 'max:100'],
            'sensibilita_ai_like' => ['required', 'integer', 'min:0', 'max:100'],
            'ritmo_attivita' => ['required', 'string', 'in:basso,medio,alto,normale'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Inserisci il nome del tuo avatar.',
            'nome.max' => 'Il nome non può superare i 120 caratteri.',
            'sesso.max' => 'Il valore del sesso non è valido.',
            'orientamento_sessuale.required' => 'Inserisci l\'orientamento sessuale.',
            'orientamento_sessuale.max' => 'L\'orientamento sessuale è troppo lungo.',
            'lavoro.required' => 'Inserisci il lavoro del tuo avatar.',
            'lavoro.max' => 'Il lavoro non può superare i 160 caratteri.',
            'orientamento_politico.required' => 'Inserisci l\'orientamento politico.',
            'orientamento_politico.max' => 'L\'orientamento politico è troppo lungo.',
            'passioni.required' => 'Inserisci almeno una passione.',
            'passioni.max' => 'Le passioni non possono superare i 500 caratteri.',
            'bias_informativo.required' => 'Descrivi il bias informativo.',
            'bias_informativo.min' => 'Il bias informativo deve avere almeno 10 caratteri.',
            'bias_informativo.max' => 'Il bias informativo non può superare i 2000 caratteri.',
            'personalita.required' => 'Descrivi la personalità.',
            'personalita.min' => 'La personalità deve avere almeno 10 caratteri.',
            'personalita.max' => 'La personalità non può superare i 2000 caratteri.',
            'stile_comunicativo.required' => 'Descrivi lo stile comunicativo.',
            'stile_comunicativo.min' => 'Lo stile comunicativo deve avere almeno 5 caratteri.',
            'stile_comunicativo.max' => 'Lo stile comunicativo non può superare i 1000 caratteri.',
            'atteggiamento_verso_attualita.required' => 'Descrivi l\'atteggiamento verso l\'attualità.',
            'atteggiamento_verso_attualita.min' => 'L\'atteggiamento verso l\'attualità deve avere almeno 5 caratteri.',
            'atteggiamento_verso_attualita.max' => 'L\'atteggiamento verso l\'attualità non può superare i 1000 caratteri.',
            'propensione_al_conflitto.required' => 'Inserisci la propensione al conflitto.',
            'propensione_al_conflitto.min' => 'La propensione al conflitto deve essere tra 0 e 100.',
            'propensione_al_conflitto.max' => 'La propensione al conflitto deve essere tra 0 e 100.',
            'sensibilita_ai_like.required' => 'Inserisci la sensibilità ai like.',
            'sensibilita_ai_like.min' => 'La sensibilità ai like deve essere tra 0 e 100.',
            'sensibilita_ai_like.max' => 'La sensibilità ai like deve essere tra 0 e 100.',
            'ritmo_attivita.required' => 'Seleziona il ritmo di attività.',
            'ritmo_attivita.in' => 'Il ritmo di attività selezionato non è valido.',
        ];
    }
}
