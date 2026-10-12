<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAccountRequest;
use App\Http\Requests\Admin\UpdateOwnPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * « Mon compte » : le pendant indispensable de la gestion des utilisateurs.
 *
 * Un administrateur crée un compte avec un mot de passe qu'il choisit et communique. Sans cet
 * écran, ce mot de passe provisoire resterait le mot de passe définitif — connu de deux
 * personnes — puisqu'aucun rôle hors administrateur n'a accès à /admin/utilisateurs.
 *
 * Accessible à TOUS les rôles : aucune permission n'est exigée sur ces routes, chacun n'y
 * modifie que son propre compte (`$request->user()`, jamais un identifiant pris dans l'URL).
 */
class AccountController extends Controller
{
    public function edit(Request $request): InertiaResponse
    {
        $user = $request->user();

        return Inertia::render('Admin/Account/Edit', [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'createdAt' => $user->created_at->translatedFormat('d F Y'),
            ],
        ]);
    }

    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('success', 'Vos informations ont été enregistrées.');
    }

    public function updatePassword(UpdateOwnPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]);

        activity()->causedBy($user)->performedOn($user)->log('Mot de passe modifié par son titulaire');

        return back()->with('success', 'Votre mot de passe a été changé. '
            .'Utilisez-le dès votre prochaine connexion.');
    }
}
