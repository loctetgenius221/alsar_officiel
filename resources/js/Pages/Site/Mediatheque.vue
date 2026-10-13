<script setup lang="ts">
/**
 * Médiathèque (CLAUDE.md §4, page 16). L'audit (§10) confirme qu'aucune vidéo/podcast/kit
 * presse n'est publiquement documenté sur le site actuel — on ne comble pas ces sections
 * avec du faux contenu : vraies photos + vraie vidéo (le hero) sont montrées, le reste
 * (podcasts, dossier de presse complet) reste honnêtement "à venir" (ComingSoon). Seul le
 * logo réel est proposé au téléchargement, puisqu'il existe vraiment.
 */
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import PageCta from '@/Components/site/PageCta.vue';
import PhotoGallery from '@/Components/site/PhotoGallery.vue';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import ComingSoon from '@/Components/ui/ComingSoon.vue';
import { Download, Mic } from '@lucide/vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import type { PageImageField } from '@/types/inertia';

defineOptions({ layout: SiteLayout });

interface MediathequeSections {
    header: { kicker: string; title: string; intro: string; image: PageImageField };
    gallery: {
        kicker: string;
        title: string;
        intro: string;
        photos: { image: PageImageField; caption: string }[];
    };
    video: { kicker: string; title: string; body: string };
    podcasts: { kicker: string; title: string; coming_soon_text: string };
    presse: { kicker: string; title: string; body: string };
}

const props = defineProps<{
    sections: MediathequeSections;
    seoTitle: string | null;
    seoDescription: string | null;
}>();

const photos = computed(() =>
    props.sections.gallery.photos
        .filter((photo) => photo.image.url)
        .map((photo) => ({
            src: photo.image.url as string,
            alt: photo.image.alt,
            caption: photo.caption || undefined,
        })),
);

const photosRef = ref<HTMLElement | null>(null);
useScrollReveal(photosRef, { stagger: 0.1 });

const videoRef = ref<HTMLElement | null>(null);
useScrollReveal(videoRef, { stagger: 0.1 });

const pressRef = ref<HTMLElement | null>(null);
useScrollReveal(pressRef, { stagger: 0.1 });
</script>

<template>
    <Head :title="seoTitle ?? undefined">
        <meta v-if="seoDescription" name="description" :content="seoDescription" />
    </Head>

    <PageHeader
        :kicker="sections.header.kicker"
        :title="sections.header.title"
        :intro="sections.header.intro"
        :image="sections.header.image.url ?? undefined"
        :image-alt="sections.header.image.alt"
        image-position="top"
    />

    <section class="mx-auto max-w-6xl px-6 py-20 sm:px-10">
        <div ref="photosRef">
            <MotifDivider align="left" size="base" />
            <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                {{ sections.gallery.kicker }}
            </p>
            <h2 class="mt-4 font-display text-display-3 text-ink">{{ sections.gallery.title }}</h2>
            <p class="mt-4 max-w-lg font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                {{ sections.gallery.intro }}
            </p>
        </div>
        <div class="mt-10">
            <PhotoGallery :images="photos" />
        </div>
    </section>

    <section class="border-y border-sand-200 bg-paper-dim">
        <div class="mx-auto max-w-6xl px-6 py-20 sm:px-10">
            <div ref="videoRef" class="grid gap-10 lg:grid-cols-12 lg:items-center lg:gap-10">
                <div class="lg:col-span-5">
                    <MotifDivider align="left" size="base" />
                    <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                        {{ sections.video.kicker }}
                    </p>
                    <h2 class="mt-4 font-display text-display-3 text-ink">{{ sections.video.title }}</h2>
                    <p
                        class="mt-4 max-w-md font-sans text-base leading-(--text-body--line-height) text-ink-soft"
                    >
                        {{ sections.video.body }}
                    </p>
                </div>
                <div class="lg:col-span-6 lg:col-start-7">
                    <div class="aspect-video overflow-hidden rounded-2xl bg-ink">
                        <video
                            src="/videos/videoHero.mp4"
                            controls
                            preload="metadata"
                            class="h-full w-full object-cover"
                        >
                            Votre navigateur ne prend pas en charge la lecture vidéo.
                        </video>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-6 py-20 sm:px-10">
        <MotifDivider align="left" size="base" />
        <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
            {{ sections.podcasts.kicker }}
        </p>
        <h2 class="mt-4 max-w-2xl font-display text-display-3 text-ink">{{ sections.podcasts.title }}</h2>

        <div class="mt-10 max-w-2xl">
            <ComingSoon
                :icon="Mic"
                title="Aucun podcast pour l'instant"
                :text="sections.podcasts.coming_soon_text"
            />
        </div>
    </section>

    <section class="border-t border-sand-200 bg-paper-dim">
        <div class="mx-auto max-w-6xl px-6 py-20 sm:px-10">
            <div ref="pressRef" class="max-w-2xl">
                <MotifDivider align="left" size="base" />
                <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                    {{ sections.presse.kicker }}
                </p>
                <h2 class="mt-4 font-display text-display-3 text-ink">{{ sections.presse.title }}</h2>
                <p class="mt-4 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    {{ sections.presse.body }}
                </p>

                <a
                    href="/images/logo-alsar.png"
                    download
                    class="mt-8 inline-flex items-center gap-2 bg-ink px-6 py-3 font-sans text-sm font-medium text-paper transition-colors hover:bg-ink-soft"
                >
                    <Download class="size-4" aria-hidden="true" />
                    Télécharger le logo ALSAR
                </a>
            </div>
        </div>
    </section>

    <PageCta
        title="Vous avez vu, vous avez lu. À vous de jouer."
        body="Photos, vidéos et communiqués racontent le terrain. L'étape suivante se passe hors de l'écran : rejoignez le parti près de chez vous."
        cta-label="Rejoindre ALSAR"
        secondary-label="Lire les actualités"
        secondary-href="/actualites"
    />
</template>
