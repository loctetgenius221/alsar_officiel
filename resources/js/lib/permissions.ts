/**
 * Lecture des permissions de l'utilisateur connecté, partagées par HandleInertiaRequests.
 *
 * À quoi ça sert, et à quoi ça ne sert PAS : ces helpers décident ce qu'on AFFICHE (entrées de
 * menu, boutons, blocs du dashboard). L'autorisation réelle reste posée côté serveur par les
 * middlewares `can:` de routes/admin.php — masquer un lien n'a jamais protégé une route.
 *
 * `usePage()` est volontairement appelé à chaque invocation plutôt que capturé une fois : c'est
 * un accesseur réactif global d'Inertia (et non un `inject`), donc utilisable hors `setup()` et
 * toujours à jour après une navigation ou un changement de compte.
 */
import { usePage } from '@inertiajs/vue3';
import type { AdminPermission } from '@/types/inertia';

export function can(permission: AdminPermission): boolean {
    return (usePage().props.auth.permissions ?? []).includes(permission);
}

export function canAny(...permissions: AdminPermission[]): boolean {
    return permissions.some(can);
}
