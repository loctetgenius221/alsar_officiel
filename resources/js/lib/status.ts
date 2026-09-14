type Tone = 'success' | 'warning' | 'danger' | 'neutral';

const MEMBER_STATUS: Record<string, { label: string; tone: Tone }> = {
    active: { label: 'Actif', tone: 'success' },
    pending: { label: 'En attente', tone: 'warning' },
    inactive: { label: 'Inactif', tone: 'neutral' },
};

const PAYMENT_STATUS: Record<string, { label: string; tone: Tone }> = {
    success: { label: 'Réussi', tone: 'success' },
    pending: { label: 'En attente', tone: 'warning' },
    failed: { label: 'Échoué', tone: 'danger' },
    refunded: { label: 'Remboursé', tone: 'neutral' },
};

const ARTICLE_STATUS: Record<string, { label: string; tone: Tone }> = {
    draft: { label: 'Brouillon', tone: 'neutral' },
    scheduled: { label: 'Programmé', tone: 'warning' },
    published: { label: 'Publié', tone: 'success' },
};

export function memberStatus(status: string) {
    return MEMBER_STATUS[status] ?? { label: status, tone: 'neutral' as Tone };
}

export function paymentStatus(status: string) {
    return PAYMENT_STATUS[status] ?? { label: status, tone: 'neutral' as Tone };
}

export function articleStatus(status: string) {
    return ARTICLE_STATUS[status] ?? { label: status, tone: 'neutral' as Tone };
}
