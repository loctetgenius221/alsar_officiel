<script setup lang="ts">
/**
 * Page de connexion admin — pleine page (pas de sidebar), même registre visuel que le site
 * public mais sobre (CLAUDE.md §6). Formulaire Inertia classique (pas de vee-validate ici,
 * 2 champs seulement, la validation utile est côté serveur : identifiants corrects ou non).
 */
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { AlertCircle, Eye, EyeOff } from '@lucide/vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

// Laravel renvoie l'échec d'authentification sur le champ `email` (sa convention), alors
// qu'il ne désigne pas ce champ en particulier — le mot de passe est tout aussi suspect.
// Affiché uniquement sous « Email », il se lisait donc comme « votre email est mal écrit ».
// Sur un formulaire à deux champs, le plus clair est de tout regrouper dans un seul bandeau
// au-dessus, qui couvre aussi bien l'échec de connexion qu'un champ laissé vide.
const errorMessages = computed(() => [form.errors.email, form.errors.password].filter(Boolean) as string[]);

function submit() {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Connexion admin" />

    <div class="flex min-h-screen items-center justify-center bg-sand-50 px-6 py-12">
        <div class="w-full max-w-sm border border-sand-200 bg-paper p-8">
            <div class="flex flex-col items-center text-center">
                <img src="/images/logo-alsar.png" alt="ALSAR" class="size-12 object-contain" />
                <h1 class="mt-4 font-display text-2xl text-ink">Back-office ALSAR</h1>
                <p class="mt-1 font-sans text-sm text-ink-soft">Connectez-vous pour continuer.</p>
            </div>

            <div
                v-if="errorMessages.length"
                role="alert"
                class="mt-6 flex gap-3 border border-danger/30 bg-danger/5 px-4 py-3 font-sans text-sm text-ink-soft"
            >
                <AlertCircle class="mt-0.5 size-4 shrink-0 text-danger" aria-hidden="true" />
                <div class="space-y-1">
                    <p v-for="message in errorMessages" :key="message">{{ message }}</p>
                </div>
            </div>

            <form class="mt-8 space-y-5" novalidate @submit.prevent="submit">
                <div>
                    <label for="email" class="font-sans text-sm text-ink-soft">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        autofocus
                        class="mt-2 w-full border bg-paper px-4 py-2.5 font-sans text-sm text-ink focus-visible:border-gold-500"
                        :class="errorMessages.length ? 'border-danger/50' : 'border-sand-300'"
                    />
                </div>

                <div>
                    <label for="password" class="font-sans text-sm text-ink-soft">Mot de passe</label>
                    <div class="relative mt-2">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            class="w-full border bg-paper px-4 py-2.5 pr-11 font-sans text-sm text-ink focus-visible:border-gold-500"
                            :class="errorMessages.length ? 'border-danger/50' : 'border-sand-300'"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-ink-soft transition-colors hover:text-ink"
                            :aria-label="
                                showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
                            "
                            @click="showPassword = !showPassword"
                        >
                            <EyeOff v-if="showPassword" class="size-4.5" aria-hidden="true" />
                            <Eye v-else class="size-4.5" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <label class="flex items-center gap-2 font-sans text-sm text-ink-soft">
                    <input v-model="form.remember" type="checkbox" class="border-sand-300" />
                    Se souvenir de moi
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-gold-500 px-5 py-3 font-sans text-sm font-medium text-ink transition-colors hover:bg-gold-600 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Connexion…' : 'Se connecter' }}
                </button>
            </form>
        </div>
    </div>
</template>
