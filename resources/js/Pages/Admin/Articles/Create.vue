<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ArticleForm, { type ArticleFormData } from '@/Components/admin/ArticleForm.vue';
import Button from '@/Components/ui/Button.vue';
import FormErrorSummary from '@/Components/ui/FormErrorSummary.vue';
import { ARTICLE_FIELD_LABELS } from '@/lib/field-labels';
import { focusFirstError } from '@/lib/form-errors';
import { useUnsavedChanges } from '@/Composables/useUnsavedChanges';
import { confirmAction } from '@/lib/confirm';
import { Save } from '@lucide/vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Nouvel article' });

defineProps<{ categories: { id: number; name: string }[] }>();

const form = useForm<ArticleFormData>({
    category_id: null,
    title: '',
    slug: '',
    excerpt: '',
    body: '',
    cover_image: null,
    remove_cover_image: false,
    seo_title: '',
    seo_description: '',
    published_at: '',
});

// Un article en cours de rédaction n'existe encore nulle part côté serveur : quitter la page
// le perd intégralement. C'est le formulaire où le garde-fou compte le plus.
const isDirty = computed(() => form.isDirty);
const { allowLeave } = useUnsavedChanges(isDirty);

function submit() {
    form.post('/admin/actualites', {
        // Le message de succès et le détail des erreurs sont pris en charge globalement
        // (lib/inertia-feedback.ts) ; il ne reste ici qu'à ramener la vue sur le champ fautif.
        onError: (errors) => focusFirstError(errors),
    });
}

async function cancel() {
    if (form.isDirty) {
        const confirmed = await confirmAction({
            title: 'Abandonner ce nouvel article ?',
            description:
                "Il n'a jamais été enregistré : tout ce que vous avez saisi ici, y compris le contenu et l'image, sera perdu.",
            confirmLabel: 'Abandonner',
            cancelLabel: 'Continuer la rédaction',
            tone: 'danger',
        });
        if (!confirmed) return;
    }

    // `router.visit` et non `window.location` : une navigation Inertia ne déclenche pas
    // `beforeunload`, l'utilisateur n'a donc pas à répondre une seconde fois à la même
    // question sous la forme du dialogue natif du navigateur.
    allowLeave();
    router.visit('/admin/actualites');
}
</script>

<template>
    <Head title="Nouvel article" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-display text-2xl text-ink">Nouvel article</h2>
            <div class="flex items-center gap-3">
                <Button variant="secondary" @click="cancel">Annuler</Button>
                <Button type="submit" :disabled="form.processing">
                    <Save class="size-4" aria-hidden="true" />
                    {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                </Button>
            </div>
        </div>

        <FormErrorSummary
            :errors="form.errors"
            :labels="ARTICLE_FIELD_LABELS"
            title="L'article n'a pas pu être enregistré"
        />

        <!-- Envoi d'image : sur une connexion mobile, un visuel de plusieurs Mo peut prendre
             une minute. Sans cette barre, l'utilisateur voit un bouton grisé sans savoir si
             quelque chose se passe — et finit par recharger la page en cours d'envoi. -->
        <p
            v-if="form.progress"
            class="border border-sand-200 bg-sand-50 px-4 py-3 font-sans text-sm text-ink-soft"
            role="status"
        >
            Envoi de l'image en cours… {{ form.progress.percentage ?? 0 }} %
            <span class="mt-2 block h-px w-full bg-sand-200">
                <span
                    class="block h-px bg-gold-500 transition-[width] duration-200"
                    :style="{ width: `${form.progress.percentage ?? 0}%` }"
                />
            </span>
        </p>

        <ArticleForm :form="form" :categories="categories" />
    </form>
</template>
