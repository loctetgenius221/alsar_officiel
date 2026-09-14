/**
 * Outils de présentation des erreurs de formulaire.
 *
 * Problème traité : Inertia renvoie un dictionnaire `{ 'sections.hero.headline': 'Le champ …' }`
 * dont les clés sont des chemins techniques, et les messages s'affichaient uniquement sous le
 * champ concerné — donc invisibles quand le champ est plus bas que le pli, dans une colonne
 * latérale, ou dans un répéteur replié. Un utilisateur non développeur cliquait « Enregistrer »
 * et croyait que rien ne s'était passé.
 *
 * Ces fonctions alimentent Components/ui/FormErrorSummary.vue (récapitulatif cliquable en tête
 * de formulaire) et le recentrage automatique sur le premier champ fautif.
 */

export type ErrorBag = Record<string, string | undefined>;

export interface ErrorEntry {
    /** Clé Inertia d'origine, ex. `sections.hero.headline`. */
    key: string;
    /** Nom lisible du champ, ex. « Héros → Titre ». */
    label: string;
    message: string;
}

/**
 * Résout le nom lisible d'une clé d'erreur :
 *   1. correspondance exacte dans `labels` ;
 *   2. correspondance avec les indices de répéteur normalisés (`slides.0.title` → `slides.*.title`),
 *      en réinjectant le rang humain de la ligne (« ligne 1 », pas « 0 ») ;
 *   3. à défaut, un dernier segment de chemin rendu lisible plutôt que le chemin complet.
 */
export function labelFor(key: string, labels: Record<string, string> = {}): string {
    if (labels[key]) return labels[key];

    const wildcard = key.replace(/\.\d+\./g, '.*.');
    if (labels[wildcard]) {
        const index = key.match(/\.(\d+)\./)?.[1];
        return index === undefined ? labels[wildcard] : `${labels[wildcard]} (ligne ${Number(index) + 1})`;
    }

    const last =
        key
            .split('.')
            .filter((segment) => !/^\d+$/.test(segment) && segment !== '*')
            .pop() ?? key;

    return last.replace(/_/g, ' ').replace(/^./, (char) => char.toUpperCase());
}

export function errorEntries(errors: ErrorBag, labels: Record<string, string> = {}): ErrorEntry[] {
    return Object.entries(errors)
        .filter((entry): entry is [string, string] => Boolean(entry[1]))
        .map(([key, message]) => ({ key, label: labelFor(key, labels), message }));
}

/** « 1 champ à corriger » / « 3 champs à corriger » — accord au singulier compris. */
export function errorCountLabel(count: number): string {
    return count <= 1 ? '1 champ à corriger' : `${count} champs à corriger`;
}

/**
 * Retrouve dans le DOM le champ correspondant à une clé d'erreur. Les formulaires posent
 * `data-error-anchor="<clé>"` sur le conteneur du champ ; on retombe ensuite sur l'`id` puis
 * sur le `name`, qui couvrent les champs simples déjà en place.
 */
export function findErrorElement(key: string): HTMLElement | null {
    // Remontée par segments : le serveur peut signaler un sous-champ qui n'a pas d'ancre
    // propre (`sections.hero.image.file`, `sections.hero.image.alt`) alors que le champ
    // affiché à l'écran est son parent (`sections.hero.image`). On essaie donc le chemin
    // complet, puis de plus en plus court, plutôt que d'abandonner à la première tentative.
    const segments = key.split('.');

    for (let length = segments.length; length > 0; length--) {
        const candidate = segments.slice(0, length).join('.');
        // Valeur d'attribut entre guillemets : seuls `"` et `\` sont à échapper (pas besoin de
        // `CSS.escape`, qui vise les identifiants et n'existe pas sous jsdom).
        const escaped = candidate.replace(/["\\]/g, '\\$&');

        const element =
            document.querySelector<HTMLElement>(`[data-error-anchor="${escaped}"]`) ??
            document.getElementById(candidate) ??
            // Le serveur nomme les champs en snake_case (`first_name`), le wizard d'adhésion
            // les identifie en camelCase (`firstName`, hérité des schémas zod côté client) :
            // on tente donc aussi cette forme avant d'abandonner.
            document.getElementById(toCamelCase(candidate)) ??
            document.querySelector<HTMLElement>(`[name="${escaped}"]`);

        if (element) return element;
    }

    return null;
}

function toCamelCase(value: string): string {
    return value.replace(/_([a-z])/g, (_, letter: string) => letter.toUpperCase());
}

/**
 * Ramène la vue sur le premier champ en erreur et y place le curseur.
 *
 * `block: 'center'` plutôt que `'start'` : en haut de zone visible, le champ se retrouverait
 * masqué par les en-têtes collants (barre d'ancres de l'éditeur de contenu, header du
 * back-office). Le focus est demandé sans défilement supplémentaire pour ne pas annuler le
 * recentrage qu'on vient de faire.
 */
export function focusFirstError(errors: ErrorBag): void {
    const firstKey = Object.keys(errors).find((key) => errors[key]);
    if (!firstKey) return;

    // Le rendu du récapitulatif d'erreurs décale la page : on attend la frame suivante pour
    // mesurer la bonne position.
    requestAnimationFrame(() => {
        const element = findErrorElement(firstKey);
        if (!element) return;

        const behavior: ScrollBehavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches
            ? 'auto'
            : 'smooth';
        element.scrollIntoView({ behavior, block: 'center' });

        const focusable = element.matches('input, select, textarea, button')
            ? element
            : element.querySelector<HTMLElement>('input, select, textarea, [contenteditable="true"], button');
        focusable?.focus({ preventScroll: true });
    });
}
