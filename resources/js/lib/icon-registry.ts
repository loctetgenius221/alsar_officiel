/**
 * Liste blanche d'icônes sélectionnables depuis le back-office (axes du Programme) — miroir
 * de config('icons.programme_axis'). Toutes déjà utilisées ailleurs dans l'app (grep réel des
 * imports @lucide/vue), pour ne jamais lister une icône jamais vue sur le site.
 */
import {
    Briefcase,
    Wheat,
    Route,
    LandPlot,
    HeartHandshake,
    ShieldCheck,
    MapPin,
    ScrollText,
    Newspaper,
    CheckCircle2,
    CircleCheck,
    Mail,
    Video,
    Mic,
    Star,
    Download,
} from '@lucide/vue';
import type { Component } from 'vue';

export const ICON_REGISTRY: Record<string, Component> = {
    Briefcase,
    Wheat,
    Route,
    LandPlot,
    HeartHandshake,
    ShieldCheck,
    MapPin,
    ScrollText,
    Newspaper,
    CheckCircle2,
    CircleCheck,
    Mail,
    Video,
    Mic,
    Star,
    Download,
};

export const ICON_NAMES = Object.keys(ICON_REGISTRY);

/** Repli sur une icône neutre si une donnée en base référence un nom retiré de la liste. */
export function resolveIcon(name: string | null | undefined): Component {
    return (name && ICON_REGISTRY[name]) || ScrollText;
}
