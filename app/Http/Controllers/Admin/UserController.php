<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Support\AdminRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Comptes du back-office (CLAUDE.md §6 « Utilisateurs & rôles admin »).
 *
 * Trois garde-fous structurent tout cet écran — ce sont eux qui évitent qu'une manipulation
 * banale rende le back-office inaccessible :
 *   1. on ne modifie ni son propre rôle ni son propre accès (sinon on se retire les droits
 *      soi-même, et plus personne ne peut les rétablir) ;
 *   2. le dernier administrateur actif ne peut être ni bloqué ni rétrogradé ;
 *   3. aucune suppression de compte : on bloque. Les articles gardent leur auteur, et un
 *      accès retiré par erreur se rend en un clic.
 */
class UserController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $current = $request->user();

        $users = User::query()
            ->with('roles:id,name')
            ->when(
                $request->string('search')->trim()->isNotEmpty(),
                function (Builder $query) use ($request) {
                    $term = '%'.$request->string('search')->trim().'%';
                    $query->where(fn (Builder $q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
                },
            )
            ->when(
                $request->filled('role'),
                fn (Builder $query) => $query->whereHas('roles', fn (Builder $q) => $q->where('name', $request->string('role'))),
            )
            ->when($request->filled('status'), function (Builder $query) use ($request) {
                match ($request->string('status')->value()) {
                    'blocked' => $query->whereNotNull('blocked_at'),
                    'active' => $query->whereNull('blocked_at'),
                    default => null,
                };
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                // Identifiant public (UUID) et jamais la clé primaire : c'est lui qui sert à
                // construire les URLs côté Vue. Cf. App\Support\HasPublicId.
                'publicId' => $user->getRouteKey(),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'blocked' => $user->isBlocked(),
                'blockedAt' => $user->blocked_at?->translatedFormat('d M Y'),
                'createdAt' => $user->created_at->translatedFormat('d M Y'),
                // Calculé côté serveur : l'interface grise les actions impossibles au lieu de
                // les proposer puis de les refuser.
                'isCurrent' => $current !== null && $user->is($current),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only('search', 'role', 'status'),
            'roles' => AdminRoles::options(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => AdminRoles::options(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // `forceFill` et non `create` : `email_verified_at` n'est pas dans les attributs
        // assignables en masse du modèle (et n'a pas à l'être — aucun formulaire ne l'envoie).
        // Un compte créé par un administrateur est vérifié d'emblée : l'adresse est saisie par
        // quelqu'un qui connaît la personne. Le hachage du mot de passe reste assuré par le
        // cast `hashed` du modèle, que l'on passe par create ou par forceFill.
        $user = new User;
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'email_verified_at' => now(),
        ])->save();

        $user->syncRoles([$data['role']]);

        activity()->causedBy($request->user())->performedOn($user)
            ->log("Compte « {$user->name} » créé avec le rôle {$data['role']}");

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Le compte de {$user->name} est actif. Communiquez-lui son mot de passe "
                .'de vive voix : il pourra le changer lui-même depuis « Mon compte ».');
    }

    public function edit(User $user): InertiaResponse
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'publicId' => $user->getRouteKey(),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'blocked' => $user->isBlocked(),
                'blockedAt' => $user->blocked_at?->translatedFormat('d F Y à H\hi'),
                'createdAt' => $user->created_at->translatedFormat('d F Y'),
                'isCurrent' => $user->is(Auth::user()),
                'isLastSuperAdmin' => $this->isLastActiveSuperAdmin($user),
            ],
            'roles' => AdminRoles::options(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $previousRole = $user->roles->first()?->name;

        if ($data['role'] !== $previousRole) {
            $this->guardRoleChange($request, $user);
        }

        $user->update(['name' => $data['name'], 'email' => $data['email']]);
        $user->syncRoles([$data['role']]);

        if ($data['role'] !== $previousRole) {
            activity()->causedBy($request->user())->performedOn($user)
                ->log("Rôle de « {$user->name} » changé de {$previousRole} en {$data['role']}");
        }

        return back()->with('success', $data['role'] !== $previousRole
            ? "Le compte de {$user->name} a été enregistré. Son rôle est désormais « {$data['role']} » : "
                .'le changement prend effet à sa prochaine page.'
            : "Les informations de {$user->name} ont été enregistrées.");
    }

    public function updatePassword(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update(['password' => $request->validated('password')]);

        // Le mot de passe lui-même n'apparaît évidemment jamais dans le journal (CLAUDE.md §12).
        activity()->causedBy($request->user())->performedOn($user)
            ->log("Mot de passe de « {$user->name} » réinitialisé");

        return back()->with('success', "Le mot de passe de {$user->name} a été remplacé. "
            .'Transmettez-le-lui, puis invitez-le à le changer depuis « Mon compte ».');
    }

    public function toggleBlock(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas bloquer votre propre compte : '
                .'vous perdriez immédiatement l\'accès au back-office.');
        }

        if (! $user->isBlocked() && $this->isLastActiveSuperAdmin($user)) {
            return back()->with('error', "{$user->name} est le dernier administrateur actif. "
                .'Nommez un autre administrateur avant de bloquer ce compte, sinon plus personne '
                .'ne pourra gérer les accès.');
        }

        $user->forceFill(['blocked_at' => $user->isBlocked() ? null : now()])->save();

        $blocked = $user->isBlocked();

        activity()->causedBy($request->user())->performedOn($user)
            ->log($blocked ? "Accès de « {$user->name} » suspendu" : "Accès de « {$user->name} » rétabli");

        // On nomme le nouvel état et sa conséquence concrète, pas l'action effectuée.
        return back()->with('success', $blocked
            ? "L'accès de {$user->name} est suspendu : sa session en cours a été fermée et il ne "
                .'peut plus se connecter.'
            : "{$user->name} peut de nouveau se connecter avec son mot de passe habituel.");
    }

    /**
     * Refuse de retirer le rôle du dernier administrateur actif, ou de modifier son propre rôle.
     *
     * @throws ValidationException
     */
    private function guardRoleChange(Request $request, User $user): void
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages([
                'role' => 'Vous ne pouvez pas changer votre propre rôle. Demandez à un autre '
                    .'administrateur de le faire.',
            ]);
        }

        if ($this->isLastActiveSuperAdmin($user)) {
            throw ValidationException::withMessages([
                'role' => "{$user->name} est le dernier administrateur actif. Nommez d'abord un "
                    .'autre administrateur, sinon plus personne ne pourra gérer les comptes.',
            ]);
        }
    }

    /** Dernier compte à la fois administrateur et non bloqué. */
    private function isLastActiveSuperAdmin(User $user): bool
    {
        if (! $user->isSuperAdmin() || $user->isBlocked()) {
            return false;
        }

        return ! User::role(AdminRoles::SUPER_ADMIN)
            ->whereNull('blocked_at')
            ->whereKeyNot($user->getKey())
            ->exists();
    }
}
