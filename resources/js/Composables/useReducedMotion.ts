import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Reflects `prefers-reduced-motion` live, cf. CLAUDE.md §3.5 (le motion ne doit
 * jamais retarder l'accès à l'information) et §9 (accessibilité réelle).
 */
export function useReducedMotion() {
    const prefersReducedMotion = ref(false);

    let media: MediaQueryList | null = null;
    const update = () => {
        prefersReducedMotion.value = media?.matches ?? false;
    };

    onMounted(() => {
        media = window.matchMedia('(prefers-reduced-motion: reduce)');
        update();
        media.addEventListener('change', update);
    });

    onUnmounted(() => {
        media?.removeEventListener('change', update);
    });

    return { prefersReducedMotion };
}
