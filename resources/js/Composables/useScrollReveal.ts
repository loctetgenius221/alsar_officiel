import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { onMounted, type Ref } from 'vue';
import { useReducedMotion } from './useReducedMotion';

gsap.registerPlugin(ScrollTrigger);

interface ScrollRevealOptions {
    /** Anime chaque enfant direct de `target` en cascade au lieu de `target` lui-même. */
    stagger?: number;
    y?: number;
    duration?: number;
    delay?: number;
    /** Point de déclenchement ScrollTrigger (défaut : dès que l'élément entre bien dans l'écran). */
    start?: string;
}

/**
 * Reveal générique (fondu + léger décalage vertical) au scroll — pour les blocs de texte et
 * listes qui n'ont pas besoin du découpage ligne par ligne de useGsapReveal. Coupé si
 * prefers-reduced-motion (le contenu reste visible d'emblée, sans animation).
 */
export function useScrollReveal(target: Ref<HTMLElement | null>, options: ScrollRevealOptions = {}) {
    const { prefersReducedMotion } = useReducedMotion();

    onMounted(() => {
        if (!target.value || prefersReducedMotion.value) return;

        const els = options.stagger ? Array.from(target.value.children) : target.value;

        gsap.from(els, {
            opacity: 0,
            y: options.y ?? 28,
            duration: options.duration ?? 0.8,
            delay: options.delay ?? 0,
            stagger: options.stagger,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: target.value,
                start: options.start ?? 'top 85%',
                once: true,
            },
        });
    });
}
