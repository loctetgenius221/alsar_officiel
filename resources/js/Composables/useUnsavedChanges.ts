/**
 * Garde-fou contre la perte de travail : prévient avant de quitter un formulaire dont les
 * modifications ne sont pas enregistrées.
 *
 * Cas réel visé : l'éditeur de contenu de page et le formulaire d'article sont longs (texte
 * riche, images, répéteurs). Un clic sur « Actualités » dans la barre latérale, ou sur
 * « Annuler », faisait disparaître vingt minutes de saisie sans un mot.
 *
 * Deux sorties possibles, donc deux protections :
 *   - quitter le site / recharger l'onglet → `beforeunload` (dialogue natif imposé par le
 *     navigateur, on ne peut ni le styler ni en changer le texte) ;
 *   - naviguer dans l'application → événement `before` d'Inertia, qu'on intercepte pour
 *     afficher NOTRE boîte de confirmation.
 */
import { onBeforeUnmount, onMounted, watch, type Ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { confirmAction } from '@/lib/confirm';

export interface UnsavedChangesOptions {
    title?: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
}

export function useUnsavedChanges(isDirty: Ref<boolean>, options: UnsavedChangesOptions = {}) {
    const {
        title = 'Quitter sans enregistrer ?',
        description = 'Vos modifications ne sont pas encore enregistrées. Si vous quittez cette page maintenant, elles seront définitivement perdues.',
        confirmLabel = 'Quitter sans enregistrer',
        cancelLabel = 'Rester sur la page',
    } = options;

    // Vrai le temps de rejouer la navigation que l'utilisateur vient d'accepter — sans ça, la
    // question serait reposée en boucle à chaque tentative.
    let allowNextVisit = false;

    function onBeforeUnload(event: BeforeUnloadEvent) {
        if (!isDirty.value) return;
        event.preventDefault();
        // Certains navigateurs exigent encore l'affectation de returnValue pour déclencher
        // leur propre dialogue ; le texte, lui, est ignoré partout depuis longtemps.
        event.returnValue = '';
    }

    let removeBeforeListener: (() => void) | undefined;

    onMounted(() => {
        window.addEventListener('beforeunload', onBeforeUnload);

        removeBeforeListener = router.on('before', (event) => {
            const visit = event.detail.visit;

            if (allowNextVisit || !isDirty.value) return;

            // Seules les vraies navigations comptent. Sont écartés : la soumission du formulaire
            // lui-même (post/patch), les rechargements partiels, et surtout le préchargement au
            // survol d'un lien — qui déclenche lui aussi `before` et poserait la question alors
            // que l'utilisateur n'a fait que passer la souris sur le menu.
            if (
                visit.method !== 'get' ||
                visit.prefetch ||
                visit.only.length > 0 ||
                visit.preserveState === true
            ) {
                return;
            }

            const target = visit.url.toString();

            confirmAction({ title, description, confirmLabel, cancelLabel, tone: 'danger' }).then(
                (confirmed) => {
                    if (!confirmed) return;
                    allowNextVisit = true;
                    router.visit(target, { replace: visit.replace });
                },
            );

            return false;
        });
    });

    // La remise à zéro se fait sur la navigation effective (et non dans le `.then` ci-dessus) :
    // c'est le seul moment où l'on sait que la visite autorisée a bien eu lieu.
    const removeNavigateListener = router.on('navigate', () => {
        allowNextVisit = false;
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', onBeforeUnload);
        removeBeforeListener?.();
        removeNavigateListener();
    });

    /**
     * À appeler juste avant une navigation volontaire déclenchée par le code (ex. « Annuler »
     * qui a déjà posé sa propre question) pour ne pas demander deux fois la même chose.
     */
    function allowLeave() {
        allowNextVisit = true;
    }

    // Un formulaire qui redevient propre (enregistrement réussi) ne doit plus rien bloquer.
    watch(isDirty, (dirty) => {
        if (!dirty) allowNextVisit = false;
    });

    return { allowLeave };
}
