import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import SplitType from 'split-type';
import { onMounted, onUnmounted, type Ref } from 'vue';
import { useReducedMotion } from './useReducedMotion';

gsap.registerPlugin(ScrollTrigger);

/**
 * Découpe un titre ligne par ligne et le révèle au scroll (CLAUDE.md §3.5).
 * N'anime rien si l'utilisateur préfère un motion réduit — le texte reste visible d'emblée.
 */
export function useGsapReveal(target: Ref<HTMLElement | null>) {
    const { prefersReducedMotion } = useReducedMotion();
    let split: SplitType | null = null;

    onMounted(() => {
        if (!target.value || prefersReducedMotion.value) return;

        split = new SplitType(target.value, { types: 'lines' });

        gsap.from(split.lines, {
            yPercent: 110,
            opacity: 0,
            duration: 0.9,
            ease: 'power2.out',
            stagger: 0.08,
            scrollTrigger: {
                trigger: target.value,
                start: 'top 90%',
                once: true,
            },
        });
    });

    onUnmounted(() => {
        split?.revert();
    });
}
