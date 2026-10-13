<script setup lang="ts">
/**
 * Détail d'une actualité (CLAUDE.md §4, page 15). Article réel (CMS Actualités du
 * back-office) via Site\ArticleController::show() — `null` si le slug n'existe pas ou si
 * l'article n'est pas encore publié (jamais un 404 muet). Certains articles migrés depuis
 * l'ancien contenu statique n'ont pas de corps détaillé (jamais inventé) : le composant
 * l'assume honnêtement (ComingSoon) plutôt que d'afficher un vide.
 */
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import ComingSoon from '@/Components/ui/ComingSoon.vue';
import { Newspaper } from '@lucide/vue';
import type { PublicArticle } from '@/lib/articles';

defineOptions({ layout: SiteLayout });

defineProps<{
    article:
        | (PublicArticle & {
              body: string | null;
          })
        | null;
}>();

// Photo mise en avant au-dessus de l'en-tête et du contenu (demande explicite : en plus de
// celle déjà en arrière-plan de PageHeader, pas à sa place — cf. commentaire sur PageHeader
// plus bas, laissé strictement inchangé).
</script>

<template>
    <template v-if="article">
        <!-- Titre seul : la description et les balises Open Graph sont rendues par PHP
             (App\Support\SiteSeo + Site\ArticleController), parce que les robots de Facebook
             et WhatsApp n'exécutent pas JavaScript et ne verraient jamais celles-ci. `<Head>`
             reste utile pour le titre lors des navigations SPA. -->
        <Head :title="article.title" />

        <!-- En-tête éditorial : image de fond laissée strictement inchangée (demande explicite
        de ne pas y toucher), la photo ci-dessus s'ajoute simplement au-dessus. -->
        <PageHeader
            :kicker="article.category"
            :title="article.title"
            :intro="article.date ?? undefined"
            :image="article.image ?? undefined"
            image-alt="ALSAR — photo d’illustration"
            image-position="top"
        />
        <!-- Photo mise en avant, au-dessus de l'en-tête ET du contenu — s'ajoute à celle déjà
             affichée en arrière-plan de PageHeader juste en dessous, ne la remplace pas. -->
        <div
            v-if="article.image"
            class="mx-auto -mt-38 max-w-4xl px-6 pt-24 sm:px-10 sm:pt-28 md:-mt-40 lg:-mt-45"
        >
            <div class="aspect-video overflow-hidden rounded-2xl bg-ink shadow-lg shadow-ink/10">
                <img
                    :src="article.image"
                    :alt="article.title"
                    class="h-full w-full object-cover"
                    loading="eager"
                />
            </div>
        </div>

        <div class="mx-auto max-w-3xl px-6 py-16 sm:px-10">
            <p v-if="article.excerpt" class="font-sans text-lg text-ink-soft">{{ article.excerpt }}</p>

            <div v-if="article.body" class="prose-alsar mt-6" v-html="article.body" />
            <ComingSoon
                v-else
                class="mt-6"
                :icon="Newspaper"
                title="Article complet à venir"
                text="Le contenu détaillé de cette actualité n'est pas encore disponible. Le titre, la catégorie et la date affichés ci-dessus sont réels."
            />

            <Link
                href="/actualites"
                class="relative mt-10 inline-flex items-center gap-2 pb-1 font-sans text-sm font-medium text-ink after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-gold-500 after:transition-transform after:duration-300 hover:after:scale-x-100"
            >
                ← Toutes les actualités
            </Link>
        </div>
    </template>

    <div v-else class="mx-auto max-w-3xl px-6 py-40 text-center sm:px-10">
        <p class="font-display text-2xl text-ink">Article introuvable</p>
        <Link
            href="/actualites"
            class="relative mt-6 inline-block pb-1 font-sans text-sm font-medium text-ink after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-gold-500 after:transition-transform after:duration-300 hover:after:scale-x-100"
        >
            Retour aux actualités
        </Link>
    </div>
</template>
