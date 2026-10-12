<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Réinitialisation du mot de passe d'un AUTRE compte par un administrateur : on ne demande pas
 * le mot de passe actuel de l'intéressé (l'administrateur ne le connaît pas, c'est justement
 * pour ça qu'on réinitialise). Le contrôle d'accès vient donc entièrement de `can:users.manage`.
 */
class UpdateUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'Les deux mots de passe saisis ne sont pas identiques.',
        ];
    }
}
