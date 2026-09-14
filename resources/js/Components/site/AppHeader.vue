<script setup lang="ts">
/**
 * Header superposé au hero (transparent, texte clair) tant que la page n'a pas défilé,
 * puis solidifié (fond papier, texte encre) dès le premier scroll — pattern standard des
 * sites premium à hero plein cadre. Passer `alwaysSolid` pour les pages sans hero sombre
 * (le header doit rester lisible dès le chargement, cf. SiteLayout `heroTransparent`).
 *
 * Nav du sitemap (CLAUDE.md §4) : toutes les pages listées existent désormais, ainsi que
 * /rejoignez-nous (bouton "Adhérer", Phase 2).
 */
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useWindowScroll } from '@vueuse/core';
import { DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle, DialogTrigger } from 'reka-ui';
import { Menu, X } from '@lucide/vue';
import MagneticButton from '@/Components/ui/MagneticButton.vue';
import type { SiteSettingsSections } from '@/types/inertia';

const props = withDefaults(defineProps<{ alwaysSolid?: boolean }>(), { alwaysSolid: false });

// Les `href` restent fixes en dur (ordre et nombre non modifiables depuis le back-office) :
// seuls les libellés viennent de siteSettings.header.nav_items, zippés par index — un admin
// pourrait sinon désynchroniser un libellé de sa destination réelle. Cf. plan §3/§7 (Repeater
// verrouillé en admin pour ce champ précis).
const NAV_HREFS = ['/le-parti', '/le-president', '/programme', '/actualites', '/mediatheque', '/regions'];

// Repli défensif : ne devrait jamais servir en pratique (la ligne 'site-settings' est seedée
// au déploiement), mais évite un header cassé si jamais elle manquait.
const DEFAULT_HEADER: NonNullable<SiteSettingsSections['header']> = {
    brand_text: 'ALSAR',
    logo: { url: '/images/logo-alsar.png', alt: '' },
    nav_items: [],
    cta_label: 'Adhérer',
};

const page = usePage();
const settings = computed(() => page.props.siteSettings.sections.header ?? DEFAULT_HEADER);

const navItems = computed(() =>
    (settings.value.nav_items.length ? settings.value.nav_items : DEFAULT_HEADER.nav_items).map(
        (item: { label: string }, i: number) => ({ label: item.label, href: NAV_HREFS[i] ?? '' }),
    ),
);
const mobileOpen = ref(false);

const { y: scrollY } = useWindowScroll();
const isSolid = computed(() => props.alwaysSolid || scrollY.value > 40);

// Page courante (retire la query string) pour savoir quel lien de nav surligner — une page
// de détail (ex. /actualites/mon-article) doit garder "Actualités" actif, d'où le préfixe.
const currentPath = computed(() => usePage().url.split('?')[0]);
function isActive(href: string): boolean {
    return currentPath.value === href || currentPath.value.startsWith(`${href}/`);
}
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-30 border-b transition-colors duration-300"
        :class="isSolid ? 'border-sand-200 bg-paper' : 'border-transparent bg-transparent'"
    >
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4 sm:px-10 lg:py-5">
            <Link href="/" class="flex items-center gap-2.5">
                <img :src="settings.logo.url ?? '/images/logo-alsar.png'" alt="" class="size-8 object-contain lg:size-9" />
                <span
                    class="font-display text-xl tracking-tight transition-colors duration-300 lg:text-2xl"
                    :class="isSolid ? 'text-ink' : 'text-paper'"
                >
                    {{ settings.brand_text }}
                </span>
            </Link>

            <nav class="hidden items-center gap-8 lg:flex" aria-label="Navigation principale">
                <template v-for="item in navItems" :key="item.label">
                    <Link
                        v-if="item.href"
                        :href="item.href"
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                        class="relative py-1 font-sans text-sm transition-colors duration-300 after:absolute after:inset-x-0 after:-bottom-1 after:h-px after:origin-left after:scale-x-0 after:bg-gold-500 after:transition-transform after:duration-300 hover:after:scale-x-100"
                        :class="[
                            isSolid ? 'text-ink-soft hover:text-ink' : 'text-paper/90 hover:text-paper',
                            isActive(item.href) && (isSolid ? '!text-ink font-medium after:scale-x-100' : '!text-paper font-medium after:scale-x-100'),
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                    <span
                        v-else
                        class="font-sans text-sm transition-colors duration-300"
                        :class="isSolid ? 'text-ink-soft' : 'text-paper/90'"
                        aria-disabled="true"
                    >
                        {{ item.label }}
                    </span>
                </template>
                <MagneticButton href="/rejoignez-nous">{{ settings.cta_label }}</MagneticButton>
            </nav>

            <DialogRoot v-model:open="mobileOpen">
                <DialogTrigger
                    class="p-2 transition-colors duration-300 lg:hidden"
                    :class="isSolid ? 'text-ink' : 'text-paper'"
                    aria-label="Ouvrir le menu"
                >
                    <Menu class="size-6" aria-hidden="true" />
                </DialogTrigger>
                <DialogPortal>
                    <DialogOverlay class="fixed inset-0 z-40 bg-ink/40" />
                    <DialogContent
                        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xs flex-col gap-8 bg-paper px-6 py-5"
                    >
                        <DialogTitle class="sr-only">Menu de navigation</DialogTitle>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2.5">
                                <img :src="settings.logo.url ?? '/images/logo-alsar.png'" alt="" class="size-8 object-contain" />
                                <span class="font-display text-xl text-ink">{{ settings.brand_text }}</span>
                            </span>
                            <button
                                type="button"
                                class="p-2 text-ink"
                                aria-label="Fermer le menu"
                                @click="mobileOpen = false"
                            >
                                <X class="size-6" aria-hidden="true" />
                            </button>
                        </div>
                        <nav class="flex flex-1 flex-col gap-6" aria-label="Navigation principale (mobile)">
                            <template v-for="item in navItems" :key="item.label">
                                <Link
                                    v-if="item.href"
                                    :href="item.href"
                                    :aria-current="isActive(item.href) ? 'page' : undefined"
                                    class="border-l-2 pl-4 font-sans text-lg"
                                    :class="
                                        isActive(item.href)
                                            ? 'border-gold-500 font-medium text-ink'
                                            : 'border-transparent text-ink-soft'
                                    "
                                    @click="mobileOpen = false"
                                >
                                    {{ item.label }}
                                </Link>
                                <span v-else class="border-l-2 border-transparent pl-4 font-sans text-lg text-ink-soft" aria-disabled="true">
                                    {{ item.label }}
                                </span>
                            </template>
                        </nav>
                        <MagneticButton href="/rejoignez-nous" @click="mobileOpen = false">
                            Adhérer
                        </MagneticButton>
                    </DialogContent>
                </DialogPortal>
            </DialogRoot>
        </div>
    </header>
</template>
