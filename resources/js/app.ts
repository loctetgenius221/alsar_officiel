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

gsap.registerPlugin(ScrollTrigger);

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
        createApp({ render: () => h(App, props) })
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
