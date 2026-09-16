/**
 * Envoi d'un formulaire Inertia contenant des fichiers, vers une route PATCH/PUT/DELETE.
 *
 * ── Le problème ────────────────────────────────────────────────────────────────────────────
 * PHP ne décode un corps `multipart/form-data` QUE pour les requêtes POST. C'est une règle du
 * cœur de PHP, indépendante du serveur web et de l'hébergeur. Sur un vrai PATCH multipart,
 * `$_POST` et `$_FILES` arrivent tous les deux VIDES, et le corps n'est lisible que sous forme
 * d'octets bruts via `php://input` — Laravel n'y voit donc aucun champ et aucun fichier.
 *
 * Or `useForm().patch()` envoie bien un vrai PATCH, et Inertia bascule automatiquement en
 * `multipart/form-data` dès qu'une valeur du formulaire est un `File`. Résultat : un
 * enregistrement contenant une image partait en PATCH multipart et arrivait entièrement vide
 * côté serveur. Symptômes observés : « Aucune donnée exploitable n'a été reçue » (le garde-fou
 * de UpdatePageContentRequest), et RIEN dans storage/logs/laravel.log — une erreur de
 * validation n'étant jamais journalisée par Laravel.
 *
 * Les tests Pest ne le voyaient pas : le client HTTP de test construit l'objet Request
 * directement en mémoire et ne passe jamais par le décodage multipart de PHP.
 *
 * ── La solution ────────────────────────────────────────────────────────────────────────────
 * La méthode recommandée par Inertia et Laravel : envoyer en POST (que PHP sait décoder) avec
 * un champ `_method` qui indique la méthode réelle. Laravel lit `_method` et route la requête
 * vers `Route::patch(...)` comme si le verbe avait été utilisé directement.
 *
 * On ne bascule en POST QUE lorsqu'un fichier est réellement présent : sans fichier, Inertia
 * envoie du JSON, où `null` reste `null`. En multipart, `null` deviendrait la chaîne vide
 * `''` — que UpdatePageContent interprète comme « champ vidé volontairement » et non comme
 * « champ non modifié ». Forcer le multipart en permanence risquerait donc d'effacer des
 * contenus.
 */
import type { InertiaForm } from '@inertiajs/vue3';

/** Vrai si la structure contient au moins un `File` (ou `Blob`), à n'importe quelle profondeur. */
export function containsFile(value: unknown): boolean {
    if (typeof File !== 'undefined' && value instanceof File) return true;
    if (typeof Blob !== 'undefined' && value instanceof Blob) return true;
    if (Array.isArray(value)) return value.some(containsFile);
    if (value !== null && typeof value === 'object') return Object.values(value).some(containsFile);

    return false;
}

/**
 * Équivalent de `form.patch(url, options)`, mais qui survit à la présence d'un fichier.
 *
 * `transform` est (ré)affecté à chaque appel, y compris dans le cas sans fichier : Inertia
 * conserve la dernière transformation posée sur le formulaire, et un envoi ultérieur hériterait
 * sinon d'un `_method` devenu faux.
 *
 * `T extends object` (et non `Record<string, unknown>`) : les données de formulaire sont
 * déclarées via des `interface`, qui n'ont pas de signature d'index implicite en TypeScript.
 */
export function patchWithFiles<T extends object>(
    form: InertiaForm<T>,
    url: string,
    options?: Parameters<InertiaForm<T>['patch']>[1],
): void {
    if (!containsFile(form.data())) {
        form.transform((data) => data).patch(url, options);

        return;
    }

    form.transform((data) => ({ ...data, _method: 'patch' })).post(url, options);
}
