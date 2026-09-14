/**
 * File d'attente des notifications (toasts) de l'application.
 *
 * Pourquoi pas SweetAlert / vue-toastification : leur habillage par défaut (coins très
 * arrondis, boutons pilule, dégradés, icônes d'un autre jeu que Lucide) est exactement le
 * registre « template générique » que CLAUDE.md §3 interdit, et le neutraliser coûte plus
 * de CSS que d'écrire le composant. Ici : un store minimal (ce fichier) + un rendu maison
 * (Components/ui/Toaster.vue) construit sur les primitives Reka UI déjà installées — donc
 * accessible (aria-live, pause au survol, balayage, échappement clavier) et 100 % aux tokens
 * de design ALSAR.
 *
 * Le store vit HORS de l'arbre de composants : n'importe quel fichier (page, composable,
 * écouteur Inertia dans app.ts) peut appeler `notify.success(...)` sans injection.
 */
import { reactive } from 'vue';

export type ToastTone = 'success' | 'error' | 'warning' | 'info';

export interface ToastAction {
    label: string;
    onClick: () => void;
}

export interface ToastOptions {
    /** Phrase principale, courte et au résultat obtenu — « Article publié », pas « Succès ». */
    title: string;
    /** Détail facultatif : conséquence concrète, ou ce que l'utilisateur doit faire ensuite. */
    description?: string;
    /** 0 = reste affiché jusqu'à fermeture manuelle. */
    duration?: number;
    action?: ToastAction;
}

export interface Toast extends Required<Omit<ToastOptions, 'description' | 'action'>> {
    id: number;
    tone: ToastTone;
    description?: string;
    action?: ToastAction;
}

/**
 * Durées calées sur le temps de lecture réel : un succès se lit d'un coup d'œil, un message
 * d'erreur demande d'être lu en entier, éventuellement deux fois. Une notification porteuse
 * d'une action reste jusqu'à ce qu'on s'en occupe (durée 0).
 */
const DEFAULT_DURATION: Record<ToastTone, number> = {
    success: 4500,
    info: 5500,
    warning: 8000,
    error: 10000,
};

/** Au-delà, la pile masque le contenu de la page plus qu'elle n'informe. */
const MAX_VISIBLE = 3;

let nextId = 1;

export const toasts = reactive<Toast[]>([]);

function push(tone: ToastTone, options: ToastOptions): number {
    // Anti-doublon : un double-clic sur « Enregistrer », ou deux visites Inertia qui portent
    // le même flash, ne doivent pas empiler deux fois le même message — on remet simplement
    // le compteur à zéro sur celui déjà affiché.
    const existing = toasts.find(
        (toast) =>
            toast.tone === tone && toast.title === options.title && toast.description === options.description,
    );
    if (existing) {
        dismiss(existing.id);
    }

    const toast: Toast = {
        id: nextId++,
        tone,
        title: options.title,
        description: options.description,
        action: options.action,
        duration: options.duration ?? (options.action ? 0 : DEFAULT_DURATION[tone]),
    };

    toasts.push(toast);

    while (toasts.length > MAX_VISIBLE) {
        toasts.shift();
    }

    return toast.id;
}

export function dismiss(id: number): void {
    const index = toasts.findIndex((toast) => toast.id === id);
    if (index !== -1) toasts.splice(index, 1);
}

export function dismissAll(): void {
    toasts.splice(0, toasts.length);
}

/**
 * Retire toutes les notifications d'une tonalité donnée.
 *
 * Sert à ne jamais afficher deux messages qui se contredisent : une erreur de formulaire reste
 * visible dix secondes, largement de quoi corriger le champ et réussir l'enregistrement — on
 * se retrouvait alors avec « Formulaire incomplet » juste au-dessus de « Brouillon créé ».
 */
export function dismissTone(tone: ToastTone): void {
    for (let index = toasts.length - 1; index >= 0; index--) {
        if (toasts[index].tone === tone) toasts.splice(index, 1);
    }
}

export const notify = {
    success: (options: ToastOptions | string) => push('success', normalize(options)),
    error: (options: ToastOptions | string) => push('error', normalize(options)),
    warning: (options: ToastOptions | string) => push('warning', normalize(options)),
    info: (options: ToastOptions | string) => push('info', normalize(options)),
    dismiss,
    dismissAll,
    dismissTone,
};

function normalize(options: ToastOptions | string): ToastOptions {
    return typeof options === 'string' ? { title: options } : options;
}
