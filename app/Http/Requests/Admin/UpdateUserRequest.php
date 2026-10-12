<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Voir StoreUserRequest — le mot de passe a son propre écran, il n'est jamais modifié ici. */
class UpdateUserRequest extends FormRequest
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
                Rule::unique('users', 'email')->ignore($this->route('user')->getKey()),
            ],
            'role' => ['required', 'string', Rule::in(AdminRoles::names())],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Un autre compte utilise déjà cette adresse email.',
            'role.in' => 'Choisissez un rôle dans la liste proposée.',
        ];
    }
}
