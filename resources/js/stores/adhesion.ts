import { ref } from 'vue';
import { defineStore } from 'pinia';
import { useStorage } from '@vueuse/core';

/**
 * État du wizard d'adhésion (CLAUDE.md §5), persistant en localStorage (VueUse `useStorage`)
 * pour ne rien perdre si l'utilisateur quitte en cours de route. Réinitialisé après une
 * soumission réussie.
 *
 * Cotisation désactivée pour le moment (décision produit) : le wizard ne compte plus que 2
 * étapes (Identité, Engagement) — `cotisation` a été retiré de l'état. Un visiteur revenant
 * avec un ancien `alsar-adhesion-wizard` en localStorage (contenant encore `step: 3` et/ou
 * une clé `cotisation`) n'est pas un problème : `goTo()` cale désormais tout step invalide
 * sur [1,2], et une clé `cotisation` résiduelle dans le JSON stocké est simplement ignorée
 * (elle ne correspond plus à rien dans `AdhesionState`).
 */
export interface AdhesionState {
    step: number;
    identite: {
        firstName: string;
        lastName: string;
        birthDate: string;
        gender: 'homme' | 'femme' | '';
        phone: string;
        email: string;
        region: string;
        commune: string;
        address: string;
        profession: string;
        idDocumentNumber: string;
    };
    engagement: {
        section: 'standard' | 'jeunes' | 'femmes';
        referralSource: string;
        consent: boolean;
    };
}

function defaultState(): AdhesionState {
    return {
        step: 1,
        identite: {
            firstName: '',
            lastName: '',
            birthDate: '',
            gender: '',
            phone: '',
            email: '',
            region: '',
            commune: '',
            address: '',
            profession: '',
            idDocumentNumber: '',
        },
        engagement: {
            section: 'standard',
            referralSource: '',
            consent: false,
        },
    };
}

export const useAdhesionStore = defineStore('adhesion', () => {
    const state = useStorage<AdhesionState>('alsar-adhesion-wizard', defaultState());

    // Séparé de `state` (non `useStorage`) : un File ne se sérialise pas en JSON/localStorage,
    // et ce n'est de toute façon pas souhaitable de le persister — perdu au rafraîchissement,
    // comportement normal d'un champ fichier natif.
    const photoFile = ref<File | null>(null);

    function goTo(step: number) {
        state.value.step = Math.min(Math.max(step, 1), 2);
    }

    function next() {
        goTo(state.value.step + 1);
    }

    function back() {
        goTo(state.value.step - 1);
    }

    function reset() {
        state.value = defaultState();
        photoFile.value = null;
    }

    return { state, photoFile, goTo, next, back, reset };
});
