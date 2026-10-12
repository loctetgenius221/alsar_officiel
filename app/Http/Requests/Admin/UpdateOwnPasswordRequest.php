<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Changement de son propre mot de passe. Le mot de passe actuel est exigé : sans lui, un poste
 * laissé déverrouillé quelques minutes suffirait à verrouiller durablement le compte de
 * quelqu'un d'autre (CLAUDE.md §8).
 */
class UpdateOwnPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'Les deux mots de passe saisis ne sont pas identiques.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
        ];
    }
}
