import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { onMounted, type Ref } from 'vue';
import { useReducedMotion } from './useReducedMotion';

gsap.registerPlugin(ScrollTrigger);

/**
 * Parallax vertical subtil (CLAUDE.md §3.5 : "parallax subtil sur images, pas d'excès") lié
 * au scroll — `target` doit être dans un conteneur `overflow-hidden` (PhotoFrame/VideoFrame
 * le sont déjà) pour ne pas déborder du cadre. Coupé si prefers-reduced-motion.
 */
export function useParallax(target: Ref<HTMLElement | null>, distance = 12) {
    const { prefersReducedMotion } = useReducedMotion();

    onMounted(() => {
        if (!target.value || prefersReducedMotion.value) return;

        gsap.fromTo(
            target.value,
            { yPercent: -distance },
            {
                yPercent: distance,
                ease: 'none',
                scrollTrigger: {
                    trigger: target.value,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: true,
                },
            },
        );
    });
}
