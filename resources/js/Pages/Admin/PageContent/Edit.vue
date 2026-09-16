<script setup lang="ts">
/**
 * Écran d'édition générique unique pour les 7 pages de contenu (CLAUDE.md §11 : "éditeur de
 * blocs simple, pas un page-builder"). Le formulaire est entièrement piloté par `schema`
 * (App\Domain\Content\PageSectionSchemas) — ajouter un champ à une page est un changement
 * PHP seul, aucun fichier Vue à toucher. `ICON_NAMES` alimente tous les `select_icon` du
 * schéma (aujourd'hui uniquement les axes du Programme).
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, useForm, setLayoutProps } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Input from '@/Components/ui/Input.vue';
import Button from '@/Components/ui/Button.vue';
import FormErrorSummary from '@/Components/ui/FormErrorSummary.vue';
import SchemaField from '@/Components/admin/SchemaField.vue';
import { prepareFormSections, type PageSchema } from '@/lib/page-content-schema';
import { ICON_NAMES } from '@/lib/icon-registry';
import { sectionFieldLabels } from '@/lib/field-labels';
import { focusFirstError } from '@/lib/form-errors';
import { patchWithFiles } from '@/lib/inertia-upload';
import { useUnsavedChanges } from '@/Composables/useUnsavedChanges';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    page: {
        slug: string;
        label: string;
        sections: Record<string, unknown>;
        seoTitle: string | null;
        seoDescription: string | null;
    };
    schema: PageSchema;
}>();

setLayoutProps({ title: `Contenu — ${props.page.label}` });

const WIDE_TYPES = ['textarea', 'richtext', 'image', 'repeater'];

const form = useForm({
    sections: prepareFormSections(props.schema, props.page.sections),
    seo_title: props.page.seoTitle ?? '',
    seo_description: props.page.seoDescription ?? '',
});

// Formulaire long (plusieurs sections, textes riches, répéteurs de photos) : c'est ici que
// quitter la page par erreur coûte le plus cher.
const isDirty = computed(() => form.isDirty);
useUnsavedChanges(isDirty, {
    title: 'Quitter sans enregistrer le contenu ?',
    description:
        'Les modifications apportées à cette page ne sont pas encore en ligne. Si vous partez maintenant, elles seront perdues.',
});

// Noms lisibles (« Héros → Titre ») dérivés du schéma, pour que le récapitulatif d'erreurs
// n'affiche jamais un chemin technique du type `sections.hero.headline`.
const fieldLabels = computed(() => sectionFieldLabels(props.schema));

function submit() {
    // `patchWithFiles` et non `form.patch` : PHP ne décode pas un corps multipart sur un verbe
    // PATCH, un enregistrement contenant une image arrivait donc vide côté serveur (cf.
    // lib/inertia-upload.ts pour le détail).
    patchWithFiles(form, `/admin/contenu-des-pages/${props.page.slug}`, {
        preserveScroll: true,
        // Remet le repère « non enregistré » à zéro : sans ça, la page resterait marquée
        // modifiée juste après un enregistrement réussi.
        onSuccess: () => form.defaults(),
        onError: (errors) => focusFirstError(errors),
    });
}

// --- Fil d'ancres : suivi de la section visible (scroll-spy) --------------------------------
const anchorKeys = [...Object.keys(props.schema), ...(props.page.slug !== 'site-settings' ? ['seo'] : [])];
const activeAnchor = ref(anchorKeys[0] ?? '');

let observer: IntersectionObserver | null = null;
onMounted(() => {
    // `root` doit être le conteneur qui défile réellement (`<main>` dans AdminLayout, pas la
    // fenêtre) — sinon IntersectionObserver ne détecte jamais rien ici. `rootMargin` négatif en
    // haut ignore la zone couverte par la barre d'ancres sticky elle-même ; négatif en bas
    // n'active une section que lorsqu'elle occupe le haut du contenu visible, pas juste en
    // train d'apparaître au ras du bas.
    const root = document.getElementById('contenu-admin');

    observer = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
            if (visible.length > 0) {
                activeAnchor.value = visible[0].target.id.replace('section-', '');
            }
        },
        { root, rootMargin: '-64px 0px -70% 0px', threshold: 0 },
    );

    for (const key of anchorKeys) {
        const el = document.getElementById(`section-${key}`);
        if (el) observer.observe(el);
    }
});
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head :title="`Contenu — ${page.label}`" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-display text-2xl text-ink">{{ page.label }}</h2>
                <!-- Le succès est désormais annoncé par une notification (lib/inertia-feedback.ts) ;
                     ce repère-ci répond à la question inverse, celle qu'on se pose en cours de
                     travail : « me reste-t-il quelque chose à enregistrer ? ». -->
                <p v-if="form.isDirty" class="mt-1 font-sans text-sm text-warning">
                    Modifications non enregistrées
                </p>
                <p v-else-if="form.recentlySuccessful" class="mt-1 font-sans text-sm text-success">
                    Tout est enregistré.
                </p>
            </div>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
            </Button>
        </div>

        <FormErrorSummary
            :errors="form.errors"
            :labels="fieldLabels"
            title="Le contenu de la page n'a pas pu être enregistré"
        />

        <!-- Repères de section : la page peut être longue (plusieurs sections, répéteurs de
             photos) — un fil d'ancres permet de sauter directement à une section plutôt que
             de tout parcourir au scroll. `-mx-4 sm:-mx-6` fait déborder la barre jusqu'aux
             bords du conteneur (annule le padding horizontal du wrapper de AdminLayout, cf. son
             commentaire sur `<main>` — le padding vertical vit désormais sur ce wrapper, pas
             sur `<main>` lui-même, donc `sticky top-0` se cale correctement au vrai bord du
             conteneur qui défile, sans bande résiduelle). -->
        <nav
            v-if="Object.keys(schema).length > 1"
            class="scrollbar-hide sticky top-0 z-10 -mx-4 flex gap-5 overflow-x-auto border-b border-sand-200 bg-sand-50 px-4 py-2 sm:-mx-6 sm:px-6"
            aria-label="Sections de la page"
        >
            <a
                v-for="(section, sectionKey) in schema"
                :key="sectionKey"
                :href="`#section-${sectionKey}`"
                class="shrink-0 border-b-2 pb-1.5 font-sans text-xs font-medium whitespace-nowrap transition-colors"
                :class="
                    activeAnchor === sectionKey
                        ? 'border-gold-500 text-ink'
                        : 'border-transparent text-ink-soft hover:border-sand-300 hover:text-ink'
                "
            >
                {{ section.label }}
            </a>
            <a
                v-if="page.slug !== 'site-settings'"
                href="#section-seo"
                class="shrink-0 border-b-2 pb-1.5 font-sans text-xs font-medium whitespace-nowrap transition-colors"
                :class="
                    activeAnchor === 'seo'
                        ? 'border-gold-500 text-ink'
                        : 'border-transparent text-ink-soft hover:border-sand-300 hover:text-ink'
                "
            >
                SEO
            </a>
        </nav>

        <Card
            v-for="(section, sectionKey) in schema"
            :id="`section-${sectionKey}`"
            :key="sectionKey"
            class="scroll-mt-14 space-y-5"
        >
            <h3 class="font-display text-lg text-ink">{{ section.label }}</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <SchemaField
                    v-for="(field, fieldKey) in section.fields"
                    :key="fieldKey"
                    v-model="form.sections[sectionKey][fieldKey]"
                    :class="WIDE_TYPES.includes(field.type) ? 'sm:col-span-2' : ''"
                    :field="field"
                    :path="`sections.${sectionKey}.${fieldKey}`"
                    :errors="form.errors"
                    :icon-options="ICON_NAMES"
                />
            </div>
        </Card>

        <Card v-if="page.slug !== 'site-settings'" id="section-seo" class="scroll-mt-14 space-y-5">
            <h3 class="font-display text-lg text-ink">Référencement (SEO)</h3>
            <Input
                id="seo_title"
                v-model="form.seo_title"
                label="Titre SEO"
                placeholder="Laisser vide pour utiliser le titre par défaut"
                :error="form.errors.seo_title"
            />
            <div>
                <label for="seo_description" class="font-sans text-sm text-ink-soft">Description SEO</label>
                <textarea
                    id="seo_description"
                    v-model="form.seo_description"
                    rows="2"
                    maxlength="170"
                    class="mt-2 w-full border border-sand-300 bg-paper px-4 py-2.5 font-sans text-sm text-ink focus-visible:border-gold-500"
                />
                <p class="mt-1 font-sans text-xs text-ink-soft">
                    {{ (form.seo_description ?? '').length }}/170
                </p>
            </div>
        </Card>

        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
            </Button>
        </div>
    </form>
</template>
