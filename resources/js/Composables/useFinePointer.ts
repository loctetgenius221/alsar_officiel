import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Détecte un pointeur précis avec survol réel (souris/trackpad), pas un écran tactile —
 * pour réserver les micro-interactions de type "image qui suit le curseur" aux appareils
 * où elles ont un sens (CLAUDE.md §3.5 : le motion ne doit jamais gêner l'accès au contenu).
 */
export function useFinePointer() {
    const hasFinePointer = ref(false);

    let media: MediaQueryList | null = null;
    const update = () => {
        hasFinePointer.value = media?.matches ?? false;
    };

    onMounted(() => {
        media = window.matchMedia('(hover: hover) and (pointer: fine)');
        update();
        media.addEventListener('change', update);
    });

    onUnmounted(() => {
        media?.removeEventListener('change', update);
    });

    return { hasFinePointer };
}
