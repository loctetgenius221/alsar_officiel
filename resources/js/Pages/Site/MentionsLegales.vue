<script setup lang="ts">
/**
 * Mentions légales (CLAUDE.md §4, page 23). Contenu réel là où on l'a (nom du parti,
 * contact, siège, développeur) ; les informations administratives qu'on n'a pas
 * (récépissé de déclaration, hébergeur définitif — le site n'est pas encore déployé en
 * production) sont marquées <mark> "à compléter" plutôt qu'inventées. Jamais de faux
 * numéro d'enregistrement ou de faux hébergeur sur une page à valeur légale.
 */
import { Link } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';

defineOptions({ layout: SiteLayout });
</script>

<template>
    <PageHeader
        kicker="Informations légales"
        title="Mentions légales"
        intro="Dernière mise à jour : juillet 2026."
        image="/images/slider-img-3.webp"
        image-alt="Rencontre des membres d'ALSAR"
        image-position="top"
    />

    <div class="mx-auto max-w-3xl px-6 py-16 sm:px-10">
        <div class="space-y-12">
            <section>
                <h2 class="font-display text-display-4 text-ink">1. Éditeur du site</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Ce site est édité par ALSAR — Alliance pour la Synergie des Actions Républicaines,
                    parti politique sénégalais dont le siège officiel est situé Cité Keur Gorgui,
                    immeuble Hermès 2, Dakar, Sénégal.
                </p>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Récépissé de déclaration :
                    <mark class="bg-gold-100 px-1 text-ink">à compléter par ALSAR</mark>.
                </p>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Contact :
                    <a href="mailto:contact@alsar.sn" class="text-ink underline"> contact@alsar.sn </a>
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">2. Directeur de la publication</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Mamadou Racine Sy, Président d'ALSAR.
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">3. Hébergement</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    <mark class="bg-gold-100 px-1 text-ink"
                        >Nom, adresse et contact de l'hébergeur à préciser lors de la mise en production du
                        site.</mark
                    >
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">4. Conception et développement</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Site conçu et développé par
                    <a
                        href="https://innovamind.tech/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-ink underline"
                    >
                        Innovamind
                    </a>
                    .
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">5. Propriété intellectuelle</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    L'ensemble des contenus de ce site (textes, photographies, logo, mise en page) est la
                    propriété d'ALSAR ou de ses ayants droit, sauf mention contraire. Toute reproduction,
                    représentation ou diffusion, totale ou partielle, sans autorisation préalable est
                    interdite.
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">6. Données personnelles</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Les données transmises via ce site (formulaire de contact, adhésion en ligne) sont
                    traitées conformément à notre
                    <Link href="/politique-de-confidentialite" class="text-ink underline">
                        Politique de confidentialité
                    </Link>
                    .
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">7. Contact</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Pour toute question relative à ce site :
                    <a href="mailto:contact@alsar.sn" class="text-ink underline"> contact@alsar.sn </a>
                </p>
            </section>
        </div>
    </div>
</template>
