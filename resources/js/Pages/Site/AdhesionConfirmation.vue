<script setup lang="ts">
/**
 * Page de confirmation d'adhésion (CLAUDE.md §5.4) : statut explicite (pending/active),
 * jamais d'ambiguïté à l'écran. Carte numérique + QR uniquement si le statut est "active"
 * (paiement confirmé, même simulé pour l'instant).
 */
import { ref } from 'vue';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import BrandSeal from '@/Components/ui/BrandSeal.vue';
import { CheckCircle2, Download, ShieldCheck } from '@lucide/vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';

defineOptions({ layout: SiteLayout });

defineProps<{
    member: {
        membershipCode: string;
        fullName: string;
        status: 'pending' | 'active' | 'inactive';
        section: string;
        joinedAt: string | null;
    } | null;
    qrCode: string | null;
}>();

const panelRef = ref<HTMLElement | null>(null);
useScrollReveal(panelRef, { y: 16, duration: 0.7 });
</script>

<template>
    <PageHeader
        kicker="Adhésion"
        title="Confirmation"
        image="/images/slider-img-3.webp"
        image-alt="Rencontre des membres d'ALSAR"
        image-position="top"
        compact
    />

    <div class="mx-auto max-w-2xl px-6 pb-24 sm:px-10">
        <template v-if="!member">
            <p class="font-sans text-base text-ink-soft">Confirmation introuvable.</p>
        </template>

        <template v-else-if="member.status === 'active'">
            <div ref="panelRef" class="relative mt-8 border border-sand-300 bg-paper p-8 sm:p-12">
                <!-- Sceau : il n'apparaît que sur une adhésion CONFIRMÉE, et nulle part ailleurs
                     sur le site. C'est tout son sens — un cachet se pose sur un document valide,
                     pas en décoration. Il avait d'abord été placé sur le bloc d'adhésion de
                     l'accueil : PhotoStack y porte déjà un médaillon rond avec le même logo, et
                     deux marques circulaires dans le même écran se neutralisent. Masqué sous
                     `sm`, où la place manque sans empiéter sur le nom de l'adhérent. -->
                <BrandSeal class="pointer-events-none absolute top-8 right-8 hidden w-20 sm:block" />

                <div class="flex items-center gap-2 text-gold-600">
                    <CheckCircle2 class="size-5" aria-hidden="true" />
                    <p class="font-sans text-xs font-semibold tracking-wide uppercase">Adhésion confirmée</p>
                </div>

                <MotifDivider align="left" size="sm" />

                <p class="mt-4 font-display text-display-2 text-ink sm:pr-28">{{ member.fullName }}</p>
                <p class="mt-2 font-sans text-sm text-ink-soft">
                    Code d'adhésion <span class="font-medium text-ink">{{ member.membershipCode }}</span>
                    <template v-if="member.joinedAt">
                        &middot; Membre depuis le {{ member.joinedAt }}</template
                    >
                </p>

                <div
                    class="mt-10 flex flex-col gap-8 border-t border-sand-200 pt-8 sm:flex-row sm:items-center"
                >
                    <div
                        v-if="qrCode"
                        class="inline-block shrink-0 self-start border border-sand-200 bg-paper-dim p-3"
                    >
                        <img :src="qrCode" alt="QR code de vérification de l'adhésion" class="size-32" />
                    </div>

                    <div>
                        <p class="max-w-sm font-sans text-sm text-ink-soft">
                            Votre carte de membre numérique est prête, avec QR code vérifiable publiquement.
                        </p>
                        <a
                            :href="`/adhesion/carte/${member.membershipCode}`"
                            class="mt-5 inline-flex items-center gap-2 bg-ink px-6 py-3 font-sans text-sm font-medium text-paper transition-colors hover:bg-ink-soft"
                        >
                            <Download class="size-4" aria-hidden="true" />
                            Télécharger ma carte de membre (PDF)
                        </a>
                    </div>
                </div>
            </div>

            <p class="mt-6 flex items-center gap-2 font-sans text-sm text-ink-soft">
                <ShieldCheck class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                Vérifiable publiquement sur
                <a
                    :href="`/verifier/${member.membershipCode}`"
                    class="relative text-ink after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-100 after:bg-ink/30 after:transition-colors after:duration-300 hover:after:bg-ink"
                >
                    /verifier/{{ member.membershipCode }}
                </a>
            </p>
        </template>

        <template v-else>
            <div ref="panelRef" class="border border-sand-300 bg-paper-dim p-8 sm:p-12">
                <p class="font-sans text-xs tracking-wide text-gold-700 uppercase">Adhésion enregistrée</p>
                <MotifDivider align="left" size="sm" />
                <p class="mt-4 font-display text-display-2 text-ink">{{ member.fullName }}</p>
                <p class="mt-3 max-w-md font-sans text-sm text-ink-soft">
                    Votre adhésion est enregistrée mais votre cotisation n'a pas encore été réglée. Votre
                    carte de membre sera activée dès réception du paiement. Pour cotiser dès maintenant,
                    contactez-nous à
                    <a href="mailto:contact@alsar.sn" class="text-ink underline"> contact@alsar.sn </a>
                    .
                </p>
            </div>
        </template>
    </div>
</template>
