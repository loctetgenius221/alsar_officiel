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
import { useParallax } from '@/Composables/useParallax';
import type { PublicArticle } from '@/lib/articles';

defineOptions({ layout: SiteLayout });

defineProps<{
    article:
        | (PublicArticle & {
              body: string | null;
              seoTitle: string | null;
              seoDescription: string | null;
              ogImage: string;
          })
        | null;
}>();

// Photo mise en avant au-dessus de l'en-tête et du contenu (demande explicite : en plus de
// celle déjà en arrière-plan de PageHeader, pas à sa place — cf. commentaire sur PageHeader
// plus bas, laissé strictement inchangé).
const featuredImage = ref<HTMLImageElement | null>(null);
useParallax(featuredImage, 8);
</script>

<template>
    <template v-if="article">
        <Head :title="article.seoTitle || article.title">
            <meta name="description" :content="article.seoDescription || article.excerpt || undefined" />

            <!-- Open Graph / partage social (CLAUDE.md §6 : "SEO par article, title/description/OG
                 image") — og:image reprend la couverture de l'article, ou le logo à défaut plutôt
                 que de laisser les réseaux sociaux choisir une image au hasard sur la page. -->
            <meta property="og:type" content="article" />
            <meta property="og:title" :content="article.seoTitle || article.title" />
            <meta property="og:description" :content="article.seoDescription || article.excerpt || undefined" />
            <meta property="og:image" :content="article.ogImage" />
            <meta name="twitter:card" content="summary_large_image" />
        </Head>

        <!-- Photo mise en avant, au-dessus de l'en-tête ET du contenu — s'ajoute à celle déjà
             affichée en arrière-plan de PageHeader juste en dessous, ne la remplace pas. -->
        <div v-if="article.image" class="mx-auto max-w-4xl px-6 pt-24 sm:px-10 sm:pt-28">
            <div class="aspect-video overflow-hidden rounded-2xl bg-ink shadow-lg shadow-ink/10">
                <img
                    ref="featuredImage"
                    :src="article.image"
                    :alt="article.title"
                    class="h-full w-full scale-125 object-cover"
                    loading="eager"
                />
            </div>
        </div>

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
