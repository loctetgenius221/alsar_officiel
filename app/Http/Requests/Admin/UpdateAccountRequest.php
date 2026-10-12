<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Propres informations du compte connecté (écran « Mon compte ») — pas de champ rôle ici. */
class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->getKey()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Un autre compte utilise déjà cette adresse email.',
        ];
    }
}
