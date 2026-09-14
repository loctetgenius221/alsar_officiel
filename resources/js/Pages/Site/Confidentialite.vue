<script setup lang="ts">
/**
 * Politique de confidentialité (CLAUDE.md §4 page 23, §8 sécurité/conformité). Rédigée à
 * partir de l'architecture réellement prévue (Member.consent_at, loi sénégalaise sur la
 * protection des données personnelles) — certaines collectes décrites (adhésion en ligne,
 * paiement) ne sont pas encore actives (Phase 2), c'est signalé explicitement plutôt que
 * présenté comme déjà en place.
 */
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';

defineOptions({ layout: SiteLayout });
</script>

<template>
    <PageHeader
        kicker="Confidentialité"
        title="Politique de confidentialité"
        intro="Dernière mise à jour : juillet 2026. Cette politique décrit comment ALSAR collecte, utilise et protège vos données personnelles, conformément à la loi sénégalaise sur la protection des données à caractère personnel."
        image="/images/slider-img-3.webp"
        image-alt="Rencontre des membres d'ALSAR"
        image-position="top"
    />

    <div class="mx-auto max-w-3xl px-6 py-16 sm:px-10">
        <div class="space-y-12">
            <section>
                <h2 class="font-display text-display-4 text-ink">1. Responsable du traitement</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    ALSAR — Alliance pour la Synergie des Actions Républicaines, Cité Keur Gorgui,
                    immeuble Hermès 2, Dakar, Sénégal. Contact :
                    <a href="mailto:contact@alsar.sn" class="text-ink underline"> contact@alsar.sn </a>
                    .
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">2. Données que nous collectons</h2>
                <ul
                    class="mt-3 space-y-2 font-sans text-base leading-(--text-body--line-height) text-ink-soft"
                >
                    <li>
                        <strong class="text-ink">Formulaire de contact :</strong> nom, email, message que vous
                        nous adressez.
                    </li>
                    <li>
                        <strong class="text-ink">Adhésion en ligne</strong> (fonctionnalité à venir) :
                        identité, coordonnées, région/commune, et horodatage de votre consentement aux statuts
                        du parti.
                    </li>
                    <li>
                        <strong class="text-ink">Paiement en ligne</strong> (fonctionnalité à venir) : les
                        données de paiement seront traitées directement par notre prestataire de paiement
                        (mobile money ou carte bancaire) — ALSAR ne stocke jamais vos données bancaires.
                    </li>
                </ul>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    À ce jour, seul le formulaire de contact est actif sur le site.
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">3. Pourquoi nous les utilisons</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Répondre à vos messages, traiter votre adhésion et votre cotisation, vous délivrer votre
                    carte de membre, et vous informer de la vie du parti si vous y consentez.
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">4. Durée de conservation</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Vos données sont conservées le temps nécessaire aux finalités ci-dessus, puis archivées ou
                    supprimées conformément à la réglementation applicable.
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">5. Vos droits</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Conformément à la loi sénégalaise sur la protection des données à caractère personnel,
                    vous disposez d'un droit d'accès, de rectification et d'effacement de vos données.
                    Adressez votre demande à
                    <a href="mailto:contact@alsar.sn" class="text-ink underline"> contact@alsar.sn </a>
                    .
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">6. Cookies</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Ce site utilise uniquement des cookies techniques nécessaires à son fonctionnement (ex.
                    session). Aucun cookie publicitaire ou de traçage tiers n'est utilisé à ce jour.
                </p>
            </section>

            <section>
                <h2 class="font-display text-display-4 text-ink">7. Sécurité</h2>
                <p class="mt-3 font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    Les données sensibles sont chiffrées au repos et l'accès à nos systèmes est restreint aux
                    personnes habilitées du parti.
                </p>
            </section>
        </div>
    </div>
</template>
