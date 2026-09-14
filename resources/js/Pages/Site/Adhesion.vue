<script setup lang="ts">
/**
 * Wizard d'adhésion (CLAUDE.md §5) : 2 étapes gérées côté client (Pinia + localStorage, pas
 * de rechargement de page), puis une vraie soumission serveur à la fin. Cotisation désactivée
 * pour le moment (décision produit) : plus d'étape de choix de palier, `tier` n'est plus
 * jamais envoyé — le membre est activé immédiatement côté serveur (RegisterMember). Le champ
 * `tier`/la logique de paiement restent fonctionnels côté back pour une réactivation future,
 * simplement plus jamais déclenchés depuis ce wizard.
 */
import { onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import StepIdentite from '@/Components/adhesion/StepIdentite.vue';
import StepEngagement from '@/Components/adhesion/StepEngagement.vue';
import FormErrorSummary from '@/Components/ui/FormErrorSummary.vue';
import { ADHESION_FIELD_LABELS } from '@/lib/field-labels';
import { useAdhesionStore } from '@/stores/adhesion';
import { useLenis } from '@/Composables/useLenis';

defineOptions({ layout: SiteLayout });

defineProps<{
    regions: string[];
}>();

const store = useAdhesionStore();

// Normalise un `step` résiduel invalide (ex. `3`, hérité d'un ancien localStorage datant du
// wizard à 3 étapes) sur la plage valide [1,2] dès le montage.
onMounted(() => store.goTo(store.state.step));

// Le formulaire s'allonge à mesure qu'on le remplit (validation, erreurs) : sans repositionner
// la vue, "Continuer"/"Retour" peut afficher le milieu de l'étape suivante plutôt que son
// début, désorientant l'adhérent. Lenis si le motion est activé (défilement doux, cf. §3.5),
// sinon un saut instantané (`useLenis()` renvoie `null` sous prefers-reduced-motion).
const wizardRef = ref<HTMLElement | null>(null);

watch(
    () => store.state.step,
    () => {
        const lenis = useLenis();
        if (lenis) {
            lenis.scrollTo(wizardRef.value ?? 0, { offset: -120, duration: 0.8 });
        } else {
            wizardRef.value?.scrollIntoView({ behavior: 'auto', block: 'start' });
        }
    },
);

const steps = [
    { number: 1, label: 'Identité' },
    { number: 2, label: 'Engagement' },
];

const FIELD_STEP: Record<string, number> = {
    first_name: 1,
    last_name: 1,
    birth_date: 1,
    gender: 1,
    phone: 1,
    email: 1,
    region: 1,
    commune: 1,
    address: 1,
    profession: 1,
    photo: 1,
    id_document_number: 1,
    section: 2,
    referral_source: 2,
    consent: 2,
};

const form = useForm({
    website: '', // honeypot : jamais rempli par un humain
    first_name: '',
    last_name: '',
    birth_date: '',
    gender: '',
    phone: '',
    email: '',
    region: '',
    commune: '',
    address: '',
    profession: '',
    photo: null as File | null,
    id_document_number: '',
    section: 'standard',
    referral_source: '',
    consent: false,
});

function submit() {
    const { identite, engagement } = store.state;

    form.website = '';
    form.first_name = identite.firstName;
    form.last_name = identite.lastName;
    form.birth_date = identite.birthDate;
    form.gender = identite.gender;
    form.phone = `+221 ${identite.phone}`.trim();
    form.email = identite.email;
    form.region = identite.region;
    form.commune = identite.commune;
    form.address = identite.address;
    form.profession = identite.profession;
    form.photo = store.photoFile;
    form.id_document_number = identite.idDocumentNumber;
    form.section = engagement.section;
    form.referral_source = engagement.referralSource;
    form.consent = engagement.consent;

    form.post('/rejoignez-nous', {
        onSuccess: () => store.reset(),
        onError: (errors) => {
            const steps = Object.keys(errors).map((field) => FIELD_STEP[field] ?? 1);
            if (steps.length) store.goTo(Math.min(...steps));
        },
    });
}
</script>

<template>
    <PageHeader
        kicker="Rejoignez-nous"
        title="Devenez adhérent d'ALSAR"
        intro="Deux étapes. Votre progression est enregistrée automatiquement si vous devez quitter en cours de route."
        image="/images/slider-img-3.webp"
        image-alt="Rencontre des membres d'ALSAR"
        image-position="top"
        compact
    />

    <div ref="wizardRef" class="mx-auto max-w-3xl scroll-mt-32 px-6 pt-16 pb-24 sm:px-10">
        <ol class="mb-14 flex items-center gap-4" aria-label="Étapes de l'adhésion">
            <li v-for="(step, i) in steps" :key="step.number" class="flex flex-1 items-center gap-4">
                <div class="flex items-center gap-2">
                    <span
                        class="flex size-8 shrink-0 items-center justify-center font-display text-sm transition-colors duration-300"
                        :class="
                            store.state.step === step.number
                                ? 'bg-ink text-paper'
                                : store.state.step > step.number
                                  ? 'bg-gold-500 text-ink'
                                  : 'bg-sand-100 text-sand-500'
                        "
                    >
                        {{ step.number }}
                    </span>
                    <span
                        class="hidden font-sans text-sm transition-colors duration-300 sm:inline"
                        :class="store.state.step === step.number ? 'text-ink' : 'text-sand-500'"
                    >
                        {{ step.label }}
                    </span>
                </div>
                <div v-if="i < steps.length - 1" class="h-px flex-1 bg-sand-200" aria-hidden="true" />
            </li>
        </ol>

        <!-- Récapitulatif au-dessus du formulaire (et non un simple message en bas de page) :
             un refus serveur renvoie à l'étape concernée, encore faut-il que l'adhérent voie
             tout de suite CE qui est en cause. Le libellé de chaque ligne renvoie au champ. -->
        <FormErrorSummary
            :errors="form.errors"
            :labels="ADHESION_FIELD_LABELS"
            title="Votre adhésion n'a pas pu être envoyée"
            class="mb-8"
        />

        <div class="border border-sand-200 bg-paper p-6 sm:p-10">
            <StepIdentite
                v-if="store.state.step === 1"
                :regions="regions"
                :server-errors="form.errors"
                @next="store.next()"
            />
            <StepEngagement
                v-else
                :processing="form.processing"
                :server-errors="form.errors"
                @next="submit"
                @back="store.back()"
            />
        </div>
    </div>
</template>
