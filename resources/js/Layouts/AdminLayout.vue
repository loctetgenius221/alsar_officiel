<script setup lang="ts">
/**
 * Layout du back-office (CLAUDE.md §6 : même design system que le site, mais plus dense).
 * Même schéma d'invocation que SiteLayout.vue (`defineOptions({ layout })`), sans
 * Lenis/hero — un back-office n'a pas besoin de smooth scroll ni de transitions GSAP lourdes.
 *
 * Coquille d'application "shell" : la fenêtre (`h-screen overflow-hidden`) ne défile jamais
 * elle-même — seul `<main>` défile. Sidebar et header restent donc fixes à l'écran quel que
 * soit le contenu de la page, au lieu de remonter avec elle (défaut précédent).
 *
 * Sidebar fixe dès `lg`, menu tiroir (Reka UI Dialog, même pattern que AppHeader.vue côté
 * site public) en dessous — sans ça la navigation admin serait inutilisable sur mobile.
 */
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle, DialogTrigger } from 'reka-ui';
import {
    FileEdit,
    History,
    LayoutDashboard,
    LogOut,
    Menu,
    Newspaper,
    SquareArrowOutUpRight,
    UserCog,
    UserRound,
    Users,
    X,
} from '@lucide/vue';
import { can } from '@/lib/permissions';
import type { AdminPermission } from '@/types/inertia';

withDefaults(defineProps<{ title?: string }>(), { title: 'Back-office' });

const page = usePage();
const user = computed(() => page.props.auth.user);
const primaryRole = computed(() => page.props.auth.roles?.[0]);
const userInitial = computed(() => (user.value?.name?.trim()?.[0] ?? '?').toUpperCase());

const mobileOpen = ref(false);

// Regroupé par intention plutôt qu'en liste plate : avec 6 entrées et appelé à grandir,
// un simple étiquetage de section aide à repérer où se trouve une fonctionnalité au premier
// coup d'œil, sans transformer la sidebar en arborescence pliable (pas nécessaire ici).
//
// `permission` reprend exactement celle du middleware `can:` posé sur la route (routes/admin.php) :
// une entrée visible mène donc toujours à un écran accessible, et un rédacteur ne découvre pas
// l'existence d'écrans qui lui répondraient 403. Une entrée sans `permission` est commune à
// tous les rôles.
interface NavItem {
    label: string;
    href: string;
    icon: unknown;
    permission?: AdminPermission;
}

const allNavGroups: { label: string; items: NavItem[] }[] = [
    {
        label: 'Aperçu',
        items: [{ label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard }],
    },
    {
        label: 'Contenu',
        items: [
            {
                label: 'Actualités',
                href: '/admin/actualites',
                icon: Newspaper,
                permission: 'articles.manage',
            },
            {
                label: 'Contenu des pages',
                href: '/admin/contenu-des-pages',
                icon: FileEdit,
                permission: 'page-content.manage',
            },
        ],
    },
    {
        // "Paiements" retiré du menu : cotisation désactivée pour le moment (décision
        // produit). L'écran et la route restent fonctionnels (accessibles par URL directe),
        // simplement plus mis en avant tant qu'aucun paiement n'est créé.
        label: 'Adhésion',
        items: [{ label: 'Adhérents', href: '/admin/adherents', icon: Users, permission: 'members.manage' }],
    },
    {
        label: 'Système',
        items: [
            { label: 'Utilisateurs', href: '/admin/utilisateurs', icon: UserCog, permission: 'users.manage' },
            {
                label: "Journal d'audit",
                href: '/admin/journal-audit',
                icon: History,
                permission: 'activity-log.view',
            },
        ],
    },
];

// Un groupe entièrement filtré disparaît avec son étiquette : laisser « Système » seul au-dessus
// du vide donnerait l'impression d'un menu cassé.
const navGroups = computed(() =>
    allNavGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => !item.permission || can(item.permission)),
        }))
        .filter((group) => group.items.length > 0),
);

const currentPath = computed(() => page.url.split('?')[0]);
function isActive(href: string): boolean {
    return currentPath.value === href || currentPath.value.startsWith(`${href}/`);
}

function logout() {
    mobileOpen.value = false;
    router.post('/admin/logout');
}

// `<main>` est désormais le seul conteneur qui défile (cf. commentaire d'en-tête) : un léger
// ombrage sous le header au premier pixel de défilement rend cette limite lisible, même
// pattern que le header du site public qui se "solidifie" au scroll (AppHeader.vue).
const mainEl = ref<HTMLElement | null>(null);
const isScrolled = ref(false);
function onMainScroll() {
    isScrolled.value = (mainEl.value?.scrollTop ?? 0) > 2;
}

// Une navigation entre deux écrans admin ne doit pas hériter du défilement de l'écran
// précédent — Inertia réinitialise `window.scrollTo`, pas le scrollTop de `<main>` puisque
// ce n'est plus la fenêtre qui défile.
let removeNavigateListener: (() => void) | undefined;
onMounted(() => {
    removeNavigateListener = router.on('navigate', () => {
        if (mainEl.value) mainEl.value.scrollTop = 0;
        isScrolled.value = false;
    });
});
onUnmounted(() => removeNavigateListener?.());
</script>

<template>
    <a
        href="#contenu-admin"
        class="sr-only focus-visible:not-sr-only focus-visible:fixed focus-visible:top-2 focus-visible:left-2 focus-visible:z-50 focus-visible:bg-gold-500 focus-visible:px-4 focus-visible:py-2 focus-visible:text-ink"
    >
        Aller au contenu
    </a>

    <div class="flex h-screen overflow-hidden bg-sand-50">
        <!-- Sidebar sombre : contraste volontaire avec le contenu clair (bg-paper), pattern
             courant des back-offices premium — rail de navigation sombre, espace de travail
             clair. `bg-ink` reprend le navy déjà utilisé pour les sections sombres du site
             public (Hero, JoinCta...), pas une couleur inventée pour l'occasion. -->
        <aside class="hidden w-64 shrink-0 flex-col bg-ink lg:flex">
            <div class="flex items-center gap-2.5 border-b border-paper/10 px-6 py-5">
                <img src="/images/logo-alsar.png" alt="" class="size-8 object-contain" />
                <span class="font-display text-lg text-paper"
                    >ALSAR <span class="text-sm text-sand-400">Admin</span></span
                >
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-6" aria-label="Navigation back-office">
                <div v-for="(group, i) in navGroups" :key="group.label" :class="i > 0 ? 'mt-6' : ''">
                    <p
                        class="px-3 font-sans text-[0.6875rem] font-semibold tracking-wider text-paper/35 uppercase"
                    >
                        {{ group.label }}
                    </p>
                    <div class="mt-2 space-y-1">
                        <Link
                            v-for="item in group.items"
                            :key="item.label"
                            :href="item.href"
                            :aria-current="isActive(item.href) ? 'page' : undefined"
                            class="flex items-center gap-3 border-l-2 px-3 py-2.5 font-sans text-sm transition-colors duration-200"
                            :class="
                                isActive(item.href)
                                    ? 'border-gold-500 bg-gold-500/10 font-medium text-paper'
                                    : 'border-transparent text-sand-300 hover:bg-paper/5 hover:text-paper'
                            "
                        >
                            <component
                                :is="item.icon"
                                class="size-4 shrink-0"
                                :class="isActive(item.href) ? 'text-gold-500' : ''"
                                aria-hidden="true"
                            />
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </nav>

            <div class="border-t border-paper/10 p-4">
                <a
                    href="/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center justify-center gap-2 border border-paper/15 px-3 py-2.5 font-sans text-xs font-medium text-sand-300 transition-colors hover:border-paper/40 hover:text-paper"
                >
                    <SquareArrowOutUpRight class="size-3.5" aria-hidden="true" />
                    Voir le site public
                </a>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="z-10 flex shrink-0 items-center justify-between gap-3 border-b bg-paper px-4 py-4 transition-shadow duration-200 sm:px-6"
                :class="isScrolled ? 'border-sand-200 shadow-sm shadow-ink/5' : 'border-sand-200/70'"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <DialogRoot v-model:open="mobileOpen">
                        <DialogTrigger
                            class="p-2 text-ink-soft transition-colors hover:text-ink lg:hidden"
                            aria-label="Ouvrir le menu"
                        >
                            <Menu class="size-6" aria-hidden="true" />
                        </DialogTrigger>
                        <DialogPortal>
                            <DialogOverlay class="fixed inset-0 z-40 bg-ink/40" />
                            <DialogContent
                                class="fixed inset-y-0 left-0 z-50 flex w-full max-w-xs flex-col gap-6 overflow-y-auto bg-ink px-6 py-5"
                            >
                                <DialogTitle class="sr-only">Menu de navigation back-office</DialogTitle>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2.5">
                                        <img
                                            src="/images/logo-alsar.png"
                                            alt=""
                                            class="size-8 object-contain"
                                        />
                                        <span class="font-display text-lg text-paper"
                                            >ALSAR <span class="text-sm text-sand-400">Admin</span></span
                                        >
                                    </span>
                                    <button
                                        type="button"
                                        class="p-2 text-paper"
                                        aria-label="Fermer le menu"
                                        @click="mobileOpen = false"
                                    >
                                        <X class="size-6" aria-hidden="true" />
                                    </button>
                                </div>

                                <nav
                                    class="flex flex-1 flex-col"
                                    aria-label="Navigation back-office (mobile)"
                                >
                                    <div
                                        v-for="(group, i) in navGroups"
                                        :key="group.label"
                                        :class="i > 0 ? 'mt-5' : ''"
                                    >
                                        <p
                                            class="px-3 font-sans text-[0.6875rem] font-semibold tracking-wider text-paper/35 uppercase"
                                        >
                                            {{ group.label }}
                                        </p>
                                        <div class="mt-2 space-y-1">
                                            <Link
                                                v-for="item in group.items"
                                                :key="item.label"
                                                :href="item.href"
                                                :aria-current="isActive(item.href) ? 'page' : undefined"
                                                class="flex items-center gap-3 border-l-2 px-3 py-3 font-sans text-base"
                                                :class="
                                                    isActive(item.href)
                                                        ? 'border-gold-500 bg-gold-500/10 font-medium text-paper'
                                                        : 'border-transparent text-sand-300'
                                                "
                                                @click="mobileOpen = false"
                                            >
                                                <component
                                                    :is="item.icon"
                                                    class="size-4.5 shrink-0"
                                                    :class="isActive(item.href) ? 'text-gold-500' : ''"
                                                    aria-hidden="true"
                                                />
                                                {{ item.label }}
                                            </Link>
                                        </div>
                                    </div>
                                </nav>

                                <div class="space-y-3 border-t border-paper/10 pt-4">
                                    <Link
                                        href="/admin/mon-compte"
                                        class="flex items-center gap-2 px-3 py-2 font-sans text-sm text-sand-300"
                                        @click="mobileOpen = false"
                                    >
                                        <UserRound class="size-4" aria-hidden="true" />
                                        Mon compte
                                    </Link>
                                    <a
                                        href="/"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-2 px-3 py-2 font-sans text-sm text-sand-300"
                                    >
                                        <SquareArrowOutUpRight class="size-3.5" aria-hidden="true" />
                                        Voir le site public
                                    </a>
                                    <button
                                        type="button"
                                        class="flex items-center gap-2 px-3 py-2 font-sans text-sm text-danger"
                                        @click="logout"
                                    >
                                        <LogOut class="size-4" aria-hidden="true" />
                                        Se déconnecter
                                    </button>
                                </div>
                            </DialogContent>
                        </DialogPortal>
                    </DialogRoot>

                    <h1 class="truncate font-display text-lg text-ink sm:text-xl">{{ title }}</h1>
                </div>

                <div class="flex shrink-0 items-center gap-3 sm:gap-4">
                    <!-- Le bloc identité sert de porte d'entrée vers « Mon compte » : c'est là
                         qu'on va chercher son propre profil, pas dans une entrée de menu
                         noyée entre les écrans de gestion. -->
                    <Link
                        href="/admin/mon-compte"
                        class="flex shrink-0 items-center gap-3 transition-opacity hover:opacity-75"
                        :aria-current="isActive('/admin/mon-compte') ? 'page' : undefined"
                    >
                        <span class="hidden text-right leading-tight sm:block">
                            <span class="block font-sans text-sm text-ink">{{ user?.name }}</span>
                            <span class="block font-sans text-xs text-ink-soft">{{ primaryRole }}</span>
                        </span>
                        <span class="sr-only">Mon compte</span>
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gold-500 font-display text-sm text-ink"
                            aria-hidden="true"
                        >
                            {{ userInitial }}
                        </span>
                    </Link>
                    <button
                        type="button"
                        class="hidden p-2 text-ink-soft transition-colors hover:text-danger lg:block"
                        aria-label="Se déconnecter"
                        @click="logout"
                    >
                        <LogOut class="size-5" aria-hidden="true" />
                    </button>
                </div>
            </header>

            <main
                id="contenu-admin"
                ref="mainEl"
                class="flex-1 overflow-x-hidden overflow-y-auto"
                @scroll="onMainScroll"
            >
                <!-- Le padding vit sur ce wrapper interne, jamais sur `<main>` lui-même : un
                     élément `sticky` (ex. le fil d'ancres de Pages/Admin/PageContent/Edit.vue)
                     se cale sur le bord de padding du plus proche ANCÊTRE QUI DÉFILE — s'il
                     avait ce padding, `top: 0` resterait bloqué sous lui en permanence, laissant
                     une bande où le contenu défilé reste visible au-dessus de l'élément collé. -->
                <div class="px-4 py-6 sm:px-6 sm:py-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
