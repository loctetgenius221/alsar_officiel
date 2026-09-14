<script setup lang="ts">
/**
 * Rendu unique de la boîte de confirmation (cf. lib/confirm.ts pour le pourquoi). Monté une
 * seule fois globalement dans app.ts.
 *
 * Reka UI `AlertDialog` plutôt que `Dialog` : sémantique `role="alertdialog"`, focus déplacé
 * sur le bouton d'annulation à l'ouverture, fermeture par Échap, et — important ici —
 * impossible de fermer en cliquant à côté, pour qu'un clic distrait ne vaille ni oui ni non.
 */
import { AlertTriangle } from '@lucide/vue';
import {
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogOverlay,
    AlertDialogPortal,
    AlertDialogRoot,
    AlertDialogTitle,
} from 'reka-ui';
import { confirmState, settleConfirm } from '@/lib/confirm';

function onOpenChange(open: boolean) {
    // Toute fermeture non explicite (Échap, fermeture programmée) vaut « non » : sans ça,
    // l'appelant resterait bloqué indéfiniment sur sa promesse.
    if (!open) settleConfirm(false);
}
</script>

<template>
    <AlertDialogRoot :open="confirmState.open" @update:open="onOpenChange">
        <AlertDialogPortal>
            <AlertDialogOverlay class="alsar-confirm-overlay fixed inset-0 z-[70] bg-ink/50" />
            <AlertDialogContent
                class="alsar-confirm-panel fixed top-1/2 left-1/2 z-[70] w-[calc(100vw-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 border border-sand-200 bg-paper p-6 shadow-2xl shadow-ink/20 sm:p-8"
            >
                <div class="flex items-start gap-4">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center"
                        :class="confirmState.options.tone === 'danger' ? 'bg-danger/10' : 'bg-gold-500/10'"
                        aria-hidden="true"
                    >
                        <AlertTriangle
                            class="size-5"
                            :class="confirmState.options.tone === 'danger' ? 'text-danger' : 'text-gold-700'"
                        />
                    </span>

                    <div class="min-w-0 flex-1">
                        <AlertDialogTitle class="font-display text-xl leading-snug text-ink">
                            {{ confirmState.options.title }}
                        </AlertDialogTitle>
                        <AlertDialogDescription
                            v-if="confirmState.options.description"
                            class="mt-2 font-sans text-sm leading-relaxed text-ink-soft"
                        >
                            {{ confirmState.options.description }}
                        </AlertDialogDescription>
                    </div>
                </div>

                <!-- Annuler d'abord dans l'ordre du DOM (donc premier au focus clavier) et posé à
                     droite visuellement : l'action destructrice ne doit jamais être celle qu'on
                     valide par réflexe avec Entrée. -->
                <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <AlertDialogCancel
                        class="inline-flex items-center justify-center border border-sand-300 px-5 py-2.5 font-sans text-sm font-medium text-ink transition-colors hover:border-ink"
                        @click="settleConfirm(false)"
                    >
                        {{ confirmState.options.cancelLabel }}
                    </AlertDialogCancel>
                    <!-- Volontairement un <button> nu et non `AlertDialogAction` : ce dernier
                         ferme le dialogue via son propre gestionnaire de clic, qui s'exécute
                         AVANT le nôtre — `onOpenChange` résolvait donc la promesse sur « non »
                         juste avant que `settleConfirm(true)` n'ait lieu, et l'action confirmée
                         n'était jamais exécutée. Ici c'est `settleConfirm(true)` qui ferme le
                         dialogue, dans cet ordre : la réponse ne peut plus être écrasée. -->
                    <button
                        type="button"
                        class="inline-flex items-center justify-center px-5 py-2.5 font-sans text-sm font-medium transition-colors"
                        :class="
                            confirmState.options.tone === 'danger'
                                ? 'bg-danger text-paper hover:opacity-90'
                                : 'bg-gold-500 text-ink hover:bg-gold-600'
                        "
                        @click="settleConfirm(true)"
                    >
                        {{ confirmState.options.confirmLabel }}
                    </button>
                </div>
            </AlertDialogContent>
        </AlertDialogPortal>
    </AlertDialogRoot>
</template>
