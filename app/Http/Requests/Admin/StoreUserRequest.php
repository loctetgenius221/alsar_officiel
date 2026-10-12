<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * L'autorisation est portée par la route (`can:users.manage`, routes/admin.php) : la garder
 * aussi ici dupliquerait la règle à deux endroits sans rien verrouiller de plus.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // `Rule::in` plutôt qu'`exists:roles,name` : un rôle créé à la main en base mais
            // absent du catalogue ne doit pas pouvoir être attribué depuis cet écran.
            'role' => ['required', 'string', Rule::in(AdminRoles::names())],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Un compte utilise déjà cette adresse email. Vérifiez si la personne n\'a pas déjà un accès.',
            'role.in' => 'Choisissez un rôle dans la liste proposée.',
            'password.confirmed' => 'Les deux mots de passe saisis ne sont pas identiques.',
        ];
    }
}
