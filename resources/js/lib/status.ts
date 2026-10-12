/**
 * Libellé, tonalité ET icône de chaque statut — une seule source pour tout le back-office.
 *
 * Pourquoi une icône en plus de la couleur : la pastille seule fait reposer l'information sur
 * la teinte (vert/orange/rouge), ce qu'un daltonien ne distingue pas et ce qu'une impression
 * noir et blanc perd entièrement (WCAG 1.4.1, « Use of Color »). Le glyphe porte la même
 * information par une autre voie, et accélère la lecture d'un tableau dense pour tout le monde.
 *
 * Même état = même glyphe partout : « en attente » est une horloge, qu'il s'agisse d'un
 * adhérent ou d'un paiement. C'est ce que garantit ce fichier, et c'est la raison pour laquelle
 * les écrans passent par Components/admin/StatusBadge.vue plutôt que de choisir leur icône.
 */
import { CalendarClock, CheckCircle2, CircleSlash, Clock, PenLine, Undo2, XCircle } from '@lucide/vue';
import type { Component } from 'vue';

type Tone = 'success' | 'warning' | 'danger' | 'neutral';

export interface StatusDescriptor {
    label: string;
    tone: Tone;
    icon: Component;
}

const MEMBER_STATUS: Record<string, StatusDescriptor> = {
    active: { label: 'Actif', tone: 'success', icon: CheckCircle2 },
    pending: { label: 'En attente', tone: 'warning', icon: Clock },
    inactive: { label: 'Inactif', tone: 'neutral', icon: CircleSlash },
};

const PAYMENT_STATUS: Record<string, StatusDescriptor> = {
    success: { label: 'Réussi', tone: 'success', icon: CheckCircle2 },
    pending: { label: 'En attente', tone: 'warning', icon: Clock },
    failed: { label: 'Échoué', tone: 'danger', icon: XCircle },
    refunded: { label: 'Remboursé', tone: 'neutral', icon: Undo2 },
};

const ARTICLE_STATUS: Record<string, StatusDescriptor> = {
    // Le brouillon est un stylo (texte en cours), le programmé un calendrier à horloge (il
    // attend une date, pas un traitement), le publié la même coche que partout ailleurs.
    draft: { label: 'Brouillon', tone: 'neutral', icon: PenLine },
    scheduled: { label: 'Programmé', tone: 'warning', icon: CalendarClock },
    published: { label: 'Publié', tone: 'success', icon: CheckCircle2 },
};

/** Repli neutre : une valeur inconnue en base reste lisible plutôt que de casser l'affichage. */
function resolve(table: Record<string, StatusDescriptor>, status: string): StatusDescriptor {
    return table[status] ?? { label: status, tone: 'neutral', icon: CircleSlash };
}

export function memberStatus(status: string): StatusDescriptor {
    return resolve(MEMBER_STATUS, status);
}

export function paymentStatus(status: string): StatusDescriptor {
    return resolve(PAYMENT_STATUS, status);
}

export function articleStatus(status: string): StatusDescriptor {
    return resolve(ARTICLE_STATUS, status);
}

export const STATUS_TABLES = {
    member: memberStatus,
    payment: paymentStatus,
    article: articleStatus,
} as const;

export type StatusKind = keyof typeof STATUS_TABLES;
