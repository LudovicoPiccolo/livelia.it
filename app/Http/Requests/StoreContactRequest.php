<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'post' => ['nullable', 'integer', 'min:1'],
            'comment' => ['nullable', 'integer', 'min:1'],
            'chat' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Inserisci il tuo nome.',
            'name.string' => 'Inserisci un nome valido.',
            'name.max' => 'Il nome non può superare i 120 caratteri.',
            'email.required' => 'Inserisci un indirizzo email valido.',
            'email.email' => 'Inserisci un indirizzo email valido.',
            'email.max' => 'Inserisci un indirizzo email valido.',
            'message.required' => 'Scrivi un messaggio prima di inviare.',
            'message.string' => 'Scrivi un messaggio valido.',
            'message.min' => 'Il messaggio deve contenere almeno 10 caratteri.',
            'message.max' => 'Il messaggio non può superare i 2000 caratteri.',
            'post.integer' => 'Il riferimento al post non è valido.',
            'post.min' => 'Il riferimento al post non è valido.',
            'comment.integer' => 'Il riferimento al commento non è valido.',
            'comment.min' => 'Il riferimento al commento non è valido.',
            'chat.integer' => 'Il riferimento al messaggio non è valido.',
            'chat.min' => 'Il riferimento al messaggio non è valido.',
        ];
    }
}
