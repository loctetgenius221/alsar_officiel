import Lenis from 'lenis';
import gsap from 'gsap';
import { onUnmounted } from 'vue';

let instance: Lenis | null = null;

/**
 * Smooth scroll global (CLAUDE.md §3.5). Une seule instance Lenis pour toute
 * l'app, synchronisée sur le ticker GSAP pour que ScrollTrigger reste juste.
 * Désactivée automatiquement si l'utilisateur préfère un motion réduit.
 */
export function useLenis() {
    if (instance) return instance;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return null;
    }

    instance = new Lenis({
        autoRaf: false,
    });

    gsap.ticker.add((time) => {
        instance?.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);

    onUnmounted(() => {
        // Instance partagée : on ne la détruit pas au démontage d'un composant.
    });

    return instance;
}
