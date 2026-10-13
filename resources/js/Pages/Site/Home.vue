<script setup lang="ts">
/**
 * Vraie page d'accueil (CLAUDE.md §4, page 1). Remplace le scaffold de vérification des
 * tokens de la Phase 0. Direction artistique à valider ici avant de répliquer le pattern
 * sur le reste du sitemap (règle de la Phase 0/1, CLAUDE.md §11).
 */
import { setLayoutProps } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import Hero, { type HeroSection } from '@/Components/site/Hero.vue';
import PresidentTeaser, { type PresidentTeaserSection } from '@/Components/site/PresidentTeaser.vue';
import ImpactStats, { type ImpactStatsSection } from '@/Components/site/ImpactStats.vue';
import ProgramPillars, { type ProgramPillarsSection } from '@/Components/site/ProgramPillars.vue';
import NewsTeaser from '@/Components/site/NewsTeaser.vue';
import RegionsTeaser, { type RegionsTeaserSection } from '@/Components/site/RegionsTeaser.vue';
import JoinCta, { type JoinCtaSection } from '@/Components/site/JoinCta.vue';
import BrandTicker, { type BrandTickerSection } from '@/Components/site/BrandTicker.vue';
import type { PublicArticle } from '@/lib/articles';

defineOptions({ layout: SiteLayout });

interface HomeSections {
    hero: HeroSection;
    president_teaser: PresidentTeaserSection;
    impact_stats: ImpactStatsSection;
    program_pillars: ProgramPillarsSection;
    regions_teaser: RegionsTeaserSection;
    join_cta: JoinCtaSection;
    brand_ticker: BrandTickerSection;
}

defineProps<{ articles: PublicArticle[]; sections: HomeSections }>();

// Seule page avec un hero plein cadre sombre : le header y démarre transparent puis se
// solidifie au scroll (SiteLayout est solide dès le chargement sur toutes les autres pages).
setLayoutProps({ heroTransparent: true });
</script>

<template>
    <Hero :section="sections.hero" />
    <PresidentTeaser :section="sections.president_teaser" />
    <ImpactStats :section="sections.impact_stats" />
    <ProgramPillars :section="sections.program_pillars" />
    <BrandTicker :section="sections.brand_ticker" />
    <RegionsTeaser :section="sections.regions_teaser" />
    <JoinCta :section="sections.join_cta" />
    <NewsTeaser :articles="articles" />
</template>
