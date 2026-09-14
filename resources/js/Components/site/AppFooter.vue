<script setup lang="ts">
/**
 * Coordonnées réelles (docx §3). Un seul réseau social est réellement actif (Facebook) —
 * on ne pointe pas vers X/Instagram/YouTube tant qu'ils ne sont pas configurés, contrairement
 * au site actuel (audit §2.4 : icônes vers "#").
 */
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Mail, MapPin } from '@lucide/vue';
import type { SiteSettingsSections } from '@/types/inertia';

const year = new Date().getFullYear();

// Repli défensif : ne devrait jamais servir en pratique (la ligne 'site-settings' est seedée
// au déploiement), mais évite un footer cassé si jamais elle manquait.
const DEFAULT_FOOTER: NonNullable<SiteSettingsSections['footer']> = {
    brand_text: 'ALSAR',
    logo: { url: '/images/logo-alsar.png', alt: '' },
    tagline: '',
    contact_email: 'contact@alsar.sn',
    contact_address: '',
    facebook_url: '',
    copyright_text: 'ALSAR. Tous droits réservés.',
};

const page = usePage();
const settings = computed(() => page.props.siteSettings.sections.footer ?? DEFAULT_FOOTER);
</script>

<template>
    <footer class="border-t border-sand-200 bg-paper-dim">
        <div class="mx-auto grid max-w-6xl gap-12 px-6 py-16 sm:px-10 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <Link href="/" class="flex items-center gap-2.5">
                    <img :src="settings.logo.url ?? '/images/logo-alsar.png'" alt="" class="size-9 object-contain" />
                    <span class="font-display text-lg text-ink">{{ settings.brand_text }}</span>
                </Link>
                <p class="mt-4 max-w-xs font-sans text-sm text-ink-soft">
                    {{ settings.tagline }}
                </p>
            </div>

            <div class="lg:col-span-4 lg:col-start-7">
                <p class="font-sans text-xs tracking-wide text-sand-600 uppercase">Contact</p>
                <a
                    :href="`mailto:${settings.contact_email}`"
                    class="mt-3 flex items-center gap-2.5 font-sans text-sm text-ink-soft hover:text-ink"
                >
                    <Mail class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                    {{ settings.contact_email }}
                </a>
                <p class="mt-3 flex items-center gap-2.5 font-sans text-sm text-ink-soft">
                    <MapPin class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                    {{ settings.contact_address }}
                </p>
            </div>

            <div class="lg:col-span-2 lg:col-start-11">
                <p class="font-sans text-xs tracking-wide text-sand-600 uppercase">Suivre</p>
                <a
                    v-if="settings.facebook_url"
                    :href="settings.facebook_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-3 block font-sans text-sm text-ink-soft hover:text-ink"
                >
                    Facebook
                </a>
                <!-- URL de la page Facebook officielle non renseignée : pas de lien mort -->
                <span v-else class="mt-3 block font-sans text-sm text-ink-soft" aria-disabled="true">
                    Facebook
                </span>
            </div>
        </div>
        <div class="border-t border-sand-200 px-6 py-6 sm:px-10">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 sm:px-10 sm:flex-row sm:gap-3"
            >
                <p class="font-sans text-xs text-sand-600">© {{ year }} {{ settings.copyright_text }}</p>
                <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2" aria-label="Liens légaux">
                    <Link href="/mentions-legales" class="font-sans text-xs text-sand-600 hover:text-ink">
                        Mentions légales
                    </Link>
                    <Link
                        href="/politique-de-confidentialite"
                        class="font-sans text-xs text-sand-600 hover:text-ink"
                    >
                        Confidentialité
                    </Link>
                </nav>
                <p class="font-sans text-xs text-sand-600">
                    Fait avec <span aria-hidden="true">❤️</span> par
                    <a
                        href="https://innovamind.tech/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-ink-soft hover:text-ink"
                    >
                        Innovamind
                    </a>
                </p>
            </div>
        </div>
    </footer>
</template>
