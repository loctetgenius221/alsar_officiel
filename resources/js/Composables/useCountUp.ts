import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { onMounted, type Ref, ref } from 'vue';
import { useReducedMotion } from './useReducedMotion';

gsap.registerPlugin(ScrollTrigger);

/**
 * Anime un nombre de 0 à `end` quand `target` entre dans le viewport (CLAUDE.md §3.2 :
 * chiffres clés en grande serif display, animation de comptage GSAP).
 */
export function useCountUp(target: Ref<HTMLElement | null>, end: number, options?: { duration?: number }) {
    const { prefersReducedMotion } = useReducedMotion();
    const value = ref(0);

    onMounted(() => {
        if (prefersReducedMotion.value) {
            value.value = end;
            return;
        }

        const counter = { n: 0 };
        gsap.to(counter, {
            n: end,
            duration: options?.duration ?? 1.6,
            ease: 'power2.out',
            onUpdate: () => {
                value.value = Math.round(counter.n);
            },
            scrollTrigger: {
                trigger: target.value ?? undefined,
                start: 'top 85%',
                once: true,
            },
        });
    });

    return { value };
}
