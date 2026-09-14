/**
 * Boîte de confirmation applicative, en remplacement du `confirm()` natif du navigateur.
 *
 * Le `confirm()` natif posait trois problèmes concrets sur ce projet :
 *   1. il affiche l'URL du site en en-tête et un habillage système — visuellement hors sujet
 *      sur un back-office premium (CLAUDE.md §3) ;
 *   2. il ne peut porter qu'une phrase, sans distinction titre / conséquence / libellé de
 *      bouton — or « OK » ne dit pas à l'utilisateur CE QU'il s'apprête à faire ;
 *   3. il bloque le thread principal et certains navigateurs mobiles le suppriment
 *      purement et simplement (l'action s'exécutait alors sans confirmation).
 *
 * Utilisation, identique en lisibilité à l'ancien `if (!confirm(...)) return;` :
 *
 *   if (!(await confirmAction({ title: '…', confirmLabel: '…', tone: 'danger' }))) return;
 */
import { reactive } from 'vue';

export interface ConfirmOptions {
    /** Question fermée, qui nomme l'objet concerné — « Supprimer l'article « X » ? ». */
    title: string;
    /** Conséquence concrète et réversibilité. C'est la ligne qui évite les regrets. */
    description?: string;
    /** Verbe d'action, jamais « OK » — l'utilisateur doit pouvoir lire le seul bouton et savoir. */
    confirmLabel?: string;
    cancelLabel?: string;
    tone?: 'danger' | 'default';
}

interface ConfirmState {
    open: boolean;
    options: Required<Omit<ConfirmOptions, 'description'>> & { description?: string };
}

const DEFAULTS = {
    confirmLabel: 'Confirmer',
    cancelLabel: 'Annuler',
    tone: 'default' as const,
};

export const confirmState = reactive<ConfirmState>({
    open: false,
    options: { title: '', ...DEFAULTS },
});

let resolver: ((value: boolean) => void) | null = null;

export function confirmAction(options: ConfirmOptions): Promise<boolean> {
    // Une demande qui en chasse une autre (double-clic sur deux lignes de tableau) : on referme
    // la précédente sur « non » plutôt que de laisser sa promesse pendante pour toujours.
    resolver?.(false);

    confirmState.options = { ...DEFAULTS, ...options };
    confirmState.open = true;

    return new Promise<boolean>((resolve) => {
        resolver = resolve;
    });
}

/** Appelé par Components/ui/ConfirmDialog.vue — pas destiné à être utilisé ailleurs. */
export function settleConfirm(value: boolean): void {
    confirmState.open = false;
    resolver?.(value);
    resolver = null;
}
