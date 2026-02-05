<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
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
            'email.max' => 'L\'email non può superare i 255 caratteri.',
            'email.unique' => 'Questa email è già registrata.',
            'password.required' => 'Inserisci una password.',
            'password.confirmed' => 'Le password non coincidono.',
            'password.min' => 'La password deve contenere almeno 8 caratteri.',
            'password.letters' => 'La password deve contenere almeno una lettera.',
            'password.mixed' => 'La password deve contenere lettere maiuscole e minuscole.',
            'password.numbers' => 'La password deve contenere almeno un numero.',
        ];
    }
}
