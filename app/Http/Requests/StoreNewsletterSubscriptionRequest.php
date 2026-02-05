<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsletterSubscriptionRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'privacy' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Inserisci un indirizzo email valido.',
            'email.email' => 'Inserisci un indirizzo email valido.',
            'email.max' => 'Inserisci un indirizzo email valido.',
            'privacy.accepted' => 'Devi accettare l\'informativa privacy per iscriverti.',
        ];
    }
}
