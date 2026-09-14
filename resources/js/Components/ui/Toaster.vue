<script setup lang="ts">
/**
 * Rendu de la pile de notifications (cf. lib/notify.ts pour le « pourquoi pas SweetAlert »).
 * Monté une seule fois, globalement (app.ts), donc disponible aussi bien sur la vitrine que
 * dans le back-office.
 *
 * Construit sur les primitives Reka UI Toast : elles apportent gratuitement ce qu'une
 * implémentation maison rate presque toujours — annonce aux lecteurs d'écran (`aria-live`,
 * assertif pour une erreur / poli pour un succès), mise en pause du minuteur au survol et au
 * focus clavier, fermeture au balayage tactile, et raccourci F8 pour atteindre la pile au
 * clavier.
 *
 * Parti pris visuel (CLAUDE.md §3) : carte rectangulaire sur fond papier, filet de couleur
 * sémantique à gauche, titre en serif d'affichage — le même registre éditorial que le reste
 * du site, pas la pastille arrondie colorée par défaut des librairies de toasts.
 */
import { computed, type Component } from 'vue';
import {
    ToastAction,
    ToastClose,
    ToastDescription,
    ToastPortal,
    ToastProvider,
    ToastRoot,
    ToastTitle,
    ToastViewport,
} from 'reka-ui';
import { AlertTriangle, CheckCircle2, Info, X, XCircle } from '@lucide/vue';
import { dismiss, toasts, type Toast, type ToastTone } from '@/lib/notify';

const TONES: Record<ToastTone, { icon: Component; accent: string; iconColor: string; tint: string }> = {
    success: { icon: CheckCircle2, accent: 'bg-success', iconColor: 'text-success', tint: 'bg-success/10' },
    error: { icon: XCircle, accent: 'bg-danger', iconColor: 'text-danger', tint: 'bg-danger/10' },
    warning: { icon: AlertTriangle, accent: 'bg-warning', iconColor: 'text-warning', tint: 'bg-warning/10' },
    info: { icon: Info, accent: 'bg-gold-600', iconColor: 'text-gold-700', tint: 'bg-gold-500/10' },
};

const items = computed(() => toasts);

function onOpenChange(toast: Toast, open: boolean) {
    if (!open) dismiss(toast.id);
}

function runAction(toast: Toast) {
    toast.action?.onClick();
    dismiss(toast.id);
}
</script>

<template>
    <ToastProvider swipe-direction="right" :duration="5000" label="Notifications">
        <!-- Portail : sort la pile de `#app`, dont app.ts anime l'opacité à chaque navigation
             Inertia — sinon un toast déclenché par la navigation apparaîtrait en fondu avec elle.
             Déclaré AVANT les toasts : ToastRoot se téléporte dans ce viewport, qui doit donc
             déjà être monté. -->
        <ToastPortal>
            <ToastViewport
                class="fixed top-0 right-0 left-0 z-[60] flex max-h-screen w-full flex-col gap-3 p-4 outline-none sm:top-auto sm:bottom-0 sm:left-auto sm:max-w-[26rem]"
            />
        </ToastPortal>

        <ToastRoot
            v-for="toast in items"
            :key="toast.id"
            :open="true"
            :duration="toast.duration"
            :type="toast.tone === 'error' || toast.tone === 'warning' ? 'foreground' : 'background'"
            class="alsar-toast pointer-events-auto relative flex w-full overflow-hidden border border-sand-200 bg-paper shadow-lg shadow-ink/10"
            @update:open="onOpenChange(toast, $event)"
        >
            <!-- Filet de couleur : porte l'information de tonalité sans colorer toute la carte
                 (un aplat vert/rouge pleine surface serait illisible et hors registre). -->
            <span class="w-[3px] shrink-0" :class="TONES[toast.tone].accent" aria-hidden="true" />

            <div class="flex flex-1 items-start gap-3 p-4">
                <span
                    class="flex size-7 shrink-0 items-center justify-center"
                    :class="TONES[toast.tone].tint"
                    aria-hidden="true"
                >
                    <component
                        :is="TONES[toast.tone].icon"
                        class="size-4"
                        :class="TONES[toast.tone].iconColor"
                    />
                </span>

                <div class="min-w-0 flex-1">
                    <ToastTitle class="font-display text-[0.9375rem] leading-snug text-ink">
                        {{ toast.title }}
                    </ToastTitle>
                    <ToastDescription
                        v-if="toast.description"
                        class="mt-1 font-sans text-[0.8125rem] leading-relaxed text-ink-soft"
                    >
                        {{ toast.description }}
                    </ToastDescription>

                    <ToastAction
                        v-if="toast.action"
                        :alt-text="toast.action.label"
                        class="mt-3 inline-flex border border-sand-300 px-3 py-1.5 font-sans text-xs font-medium text-ink transition-colors hover:border-ink"
                        @click="runAction(toast)"
                    >
                        {{ toast.action.label }}
                    </ToastAction>
                </div>

                <ToastClose
                    class="-mt-1 -mr-1 shrink-0 p-1 text-sand-500 transition-colors hover:text-ink"
                    aria-label="Fermer la notification"
                >
                    <X class="size-4" aria-hidden="true" />
                </ToastClose>
            </div>

            <!-- Compte à rebours visible : l'utilisateur voit que le message va disparaître (et
                 qu'il se fige au survol) au lieu de le voir s'évanouir sans explication.
                 Purement décoratif, donc masqué sous prefers-reduced-motion. -->
            <span
                v-if="toast.duration > 0"
                class="alsar-toast-timer absolute inset-x-0 bottom-0 h-px origin-left"
                :class="TONES[toast.tone].accent"
                :style="{ animationDuration: `${toast.duration}ms` }"
                aria-hidden="true"
            />
        </ToastRoot>
    </ToastProvider>
</template>
