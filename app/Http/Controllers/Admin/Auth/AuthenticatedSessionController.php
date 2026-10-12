<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Requests\Admin\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuthenticatedSessionController extends Controller
{
    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Message volontairement identique que l'email existe ou non (pas d'énumération
            // de comptes, CLAUDE.md §8), mais actionnable : la casse du mot de passe est la
            // cause n°1 d'échec sur un clavier mobile.
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Vérifié APRÈS l'authentification, jamais avant : annoncer « compte suspendu » sur la
        // seule foi d'une adresse email permettrait d'énumérer les comptes existants
        // (CLAUDE.md §8). Ici, seul quelqu'un qui connaît déjà le mot de passe l'apprend.
        if ($request->user()->isBlocked()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => EnsureUserIsNotBlocked::MESSAGE,
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
