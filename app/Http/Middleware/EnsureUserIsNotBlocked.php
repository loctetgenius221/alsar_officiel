<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coupe immédiatement la session d'un compte bloqué.
 *
 * Sans ce contrôle à chaque requête, bloquer quelqu'un n'aurait aucun effet tant qu'il reste
 * connecté (la session dure 2 h et se prolonge à chaque page) : le blocage ne prendrait effet
 * qu'à sa prochaine connexion, c'est-à-dire exactement quand on n'en a plus besoin.
 */
class EnsureUserIsNotBlocked
{
    public const MESSAGE = 'Votre accès au back-office a été suspendu par un administrateur. Contactez-le pour en connaître la raison.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isBlocked()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('error', self::MESSAGE);
        }

        return $next($request);
    }
}
