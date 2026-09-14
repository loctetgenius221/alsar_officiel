<script setup lang="ts">
/**
 * Page "Régions / Implantations" (CLAUDE.md §4, page 17 ; carte interactive suggérée §9).
 * ALSAR est présent dans les 14 régions du Sénégal (information transmise par le parti,
 * 2026-08), mais seules deux implantations ont un nom/contact publiquement identifié :
 * le siège national (Dakar) et la coordination ALSAR Sagatta (Saint-Louis/Podor). Pour les
 * 12 autres régions, la carte affiche une présence confirmée sans détail inventé (pas de
 * section, contact ou action fictifs) — honnêteté des données, cf. CLAUDE.md.
 */
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import SenegalMap from '@/Components/site/SenegalMap.vue';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import { SENEGAL_REGIONS } from '@/lib/senegal-regions';
import { MapPin } from '@lucide/vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import type { PageImageField } from '@/types/inertia';

defineOptions({ layout: SiteLayout });

interface RegionInfo {
    region_name: string;
    section: string;
    contact: string;
    note: string;
}

interface RegionsSections {
    header: { kicker: string; title: string; intro: string; image: PageImageField };
    map: { caption: string };
    region_info: { regions: RegionInfo[] };
    default_note: { text: string };
    sections_locales: { kicker: string; title: string };
}

const props = defineProps<{ sections: RegionsSections; seoTitle: string | null; seoDescription: string | null }>();

const REGION_DATA = computed(
    () => Object.fromEntries(props.sections.region_info.regions.map((info) => [info.region_name, info])) as Partial<
        Record<string, RegionInfo>
    >,
);

const DEFAULT_NOTE = props.sections.default_note.text;

const selectedRegion = ref('Dakar');
const selectedInfo = computed(() => REGION_DATA.value[selectedRegion.value]);

function onSelect(name: string) {
    selectedRegion.value = name;
}

const mapCol = ref<HTMLElement | null>(null);
useScrollReveal(mapCol, { y: 0, duration: 1 });

const infoCol = ref<HTMLElement | null>(null);
useScrollReveal(infoCol, { stagger: 0.1 });

const statusRef = ref<HTMLElement | null>(null);
useScrollReveal(statusRef, { stagger: 0.03 });
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
        <div class="grid gap-12 lg:grid-cols-12 lg:items-center lg:gap-10">
            <div ref="mapCol" class="lg:col-span-7">
                <div class="aspect-square">
                    <SenegalMap interactive :selected="selectedRegion" @select="onSelect" />
                </div>
                <p class="mt-3 font-sans text-xs text-sand-600">
                    {{ sections.map.caption }}
                </p>
            </div>

            <div ref="infoCol" class="lg:col-span-5 lg:col-start-8">
                <MotifDivider align="left" size="base" />
                <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                    Région sélectionnée
                </p>
                <h2 class="mt-4 font-display text-display-3 text-ink">{{ selectedRegion }}</h2>

                <div class="mt-6 border-t border-sand-200 pt-6">
                    <template v-if="selectedInfo">
                        <p class="flex items-center gap-2 font-display text-lg text-ink">
                            <MapPin class="size-5 text-gold-600" aria-hidden="true" />
                            {{ selectedInfo.section }}
                        </p>
                        <p class="mt-3 font-sans text-sm text-ink-soft">{{ selectedInfo.note }}</p>
                        <p v-if="selectedInfo.contact" class="mt-4 font-sans text-sm text-ink-soft">
                            Responsable local·e : <span class="text-ink">{{ selectedInfo.contact }}</span>
                        </p>
                    </template>
                    <p v-else class="font-sans text-sm text-ink-soft">{{ DEFAULT_NOTE }}</p>
                </div>

                <Link
                    href="/le-parti#organisation"
                    class="group relative mt-8 inline-flex items-center gap-2 pb-1 font-sans text-sm font-medium text-ink after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-gold-500 after:transition-transform after:duration-300 hover:after:scale-x-100"
                >
                    En savoir plus sur l'organisation du parti
                </Link>
            </div>
        </div>
    </section>

    <section class="border-t border-sand-200 bg-paper-dim">
        <div class="mx-auto max-w-6xl px-6 py-20 sm:px-10">
            <MotifDivider align="left" size="base" />
            <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                {{ sections.sections_locales.kicker }}
            </p>
            <h2 class="mt-4 max-w-2xl font-display text-display-3 text-ink">
                {{ sections.sections_locales.title }}
            </h2>

            <ul ref="statusRef" class="mt-10 grid gap-x-10 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <li
                    v-for="region in SENEGAL_REGIONS"
                    :key="region.name"
                    class="flex items-center justify-between border-b border-sand-200 pb-3"
                >
                    <span class="font-sans text-sm text-ink">{{ region.name }}</span>
                    <span
                        class="font-sans text-xs tracking-wide uppercase"
                        :class="REGION_DATA[region.name] ? 'text-gold-600' : 'text-gold-500/60'"
                    >
                        {{ REGION_DATA[region.name] ? 'Coordination identifiée' : 'Présence confirmée' }}
                    </span>
                </li>
            </ul>
        </div>
    </section>
</template>
