import '../css/app.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { ZiggyVue } from 'ziggy-js';
import { configure } from 'vee-validate';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { Ziggy } from './ziggy';
import Toaster from '@/Components/ui/Toaster.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import { registerInertiaFeedback } from '@/lib/inertia-feedback';

gsap.registerPlugin(ScrollTrigger);

// Notifications et confirmations : branchées ici plutôt que dans chaque layout, pour qu'un
// message déclenché pendant une navigation (flash serveur, session expirée) survive au
// changement de page — un composant monté dans un layout serait démonté au même instant.
registerInertiaFeedback();

// Validation live sur tous les formulaires vee-validate (wizard d'adhésion) : un champ se
// valide dès qu'il est modifié, pas seulement au clic sur "Continuer".
configure({
    validateOnBlur: true,
    validateOnChange: true,
    validateOnInput: true,
    validateOnModelUpdate: true,
});

const appName = import.meta.env.VITE_APP_NAME || 'ALSAR';

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) =>
        // Inertia unwrappe lui-même `module.default` au runtime — le cast n'affecte que le typage.
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ) as Promise<DefineComponent>,
    setup({ el, App, props, plugin }) {
        // Le fragment (tableau de vnodes) place Toaster et ConfirmDialog À CÔTÉ de la page, pas
        // dedans : ils ne sont donc jamais démontés par une navigation Inertia, et une
        // confirmation ouverte ou un toast affiché survivent au changement d'écran.
        createApp({ render: () => [h(App, props), h(Toaster), h(ConfirmDialog)] })
            .use(plugin)
            .use(createPinia())
            .use(ZiggyVue, Ziggy)
            .mount(el);
    },
    progress: {
        color: '#c9962c',
    },
});

// Transitions de page douces (CLAUDE.md §3.5) — respecte prefers-reduced-motion.
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!prefersReducedMotion) {
    router.on('start', () => {
        gsap.to('#app', { opacity: 0.4, duration: 0.15, ease: 'power2.out' });
    });

    router.on('finish', () => {
        gsap.to('#app', { opacity: 1, duration: 0.25, ease: 'power2.out' });
        ScrollTrigger.refresh();
    });
}
