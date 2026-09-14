<script setup lang="ts">
/**
 * Éditeur riche (Tiptap, cf. CLAUDE.md §6 : "éditeur riche recommandé pour le CMS
 * actualités"). Jeu d'extensions volontairement restreint au StarterKit + lien : un article
 * de parti n'a pas besoin de tableaux/couleurs/police custom, et chaque extension en plus
 * est une surface de rendu HTML supplémentaire à styler des deux côtés (éditeur + public).
 * Sortie HTML brute (`editor.getHTML()`) stockée telle quelle en base — l'auteur est
 * toujours un Super-admin authentifié (cf. StoreArticleRequest), pas une saisie publique.
 */
import { onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { Bold, Heading2, Heading3, Italic, Link2, List, ListOrdered, Quote, Redo2, Undo2 } from '@lucide/vue';

const props = withDefaults(defineProps<{ modelValue: string | null; placeholder?: string }>(), {
    placeholder: "Rédigez le contenu de l'article…",
});
const emit = defineEmits<{ 'update:modelValue': [string] }>();

const editor = useEditor({
    content: props.modelValue ?? '',
    extensions: [
        StarterKit,
        Link.configure({ openOnClick: false, HTMLAttributes: { rel: 'noopener noreferrer' } }),
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    editorProps: {
        attributes: {
            class: 'prose-alsar min-h-64 px-4 py-3 font-sans text-sm text-ink focus:outline-none',
        },
    },
    onUpdate: ({ editor: instance }) => {
        emit('update:modelValue', instance.getHTML());
    },
});

// Resynchronise si le parent remplace modelValue depuis l'extérieur (ex. reset de formulaire)
// — jamais pendant la frappe (l'éditeur est déjà la source de vérité dans ce cas).
watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value ?? '', { emitUpdate: false });
        }
    },
);

onBeforeUnmount(() => editor.value?.destroy());

function setLink() {
    const previousUrl = editor.value?.getAttributes('link').href as string | undefined;
    const url = window.prompt('URL du lien', previousUrl ?? 'https://');
    if (url === null) return;

    if (url === '') {
        editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
    }

    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

const TOOLBAR_BUTTON =
    'flex size-8 items-center justify-center text-ink-soft transition-colors hover:bg-sand-100 hover:text-ink disabled:cursor-not-allowed disabled:opacity-40';
const TOOLBAR_BUTTON_ACTIVE = 'bg-gold-100/60 text-ink';
</script>

<template>
    <div class="border border-sand-300 bg-paper focus-within:border-gold-500">
        <div v-if="editor" class="flex flex-wrap items-center gap-0.5 border-b border-sand-200 px-2 py-1.5">
            <button
                type="button"
                :class="[TOOLBAR_BUTTON, editor.isActive('bold') ? TOOLBAR_BUTTON_ACTIVE : '']"
                aria-label="Gras"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="size-4" aria-hidden="true" />
            </button>
            <button
                type="button"
                :class="[TOOLBAR_BUTTON, editor.isActive('italic') ? TOOLBAR_BUTTON_ACTIVE : '']"
                aria-label="Italique"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="size-4" aria-hidden="true" />
            </button>

            <span class="mx-1 h-5 w-px bg-sand-200" aria-hidden="true" />

            <button
                type="button"
                :class="[TOOLBAR_BUTTON, editor.isActive('heading', { level: 2 }) ? TOOLBAR_BUTTON_ACTIVE : '']"
                aria-label="Titre niveau 2"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            >
                <Heading2 class="size-4" aria-hidden="true" />
            </button>
            <button
                type="button"
                :class="[TOOLBAR_BUTTON, editor.isActive('heading', { level: 3 }) ? TOOLBAR_BUTTON_ACTIVE : '']"
                aria-label="Titre niveau 3"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
            >
                <Heading3 class="size-4" aria-hidden="true" />
            </button>

            <span class="mx-1 h-5 w-px bg-sand-200" aria-hidden="true" />

            <button
                type="button"
                :class="[TOOLBAR_BUTTON, editor.isActive('bulletList') ? TOOLBAR_BUTTON_ACTIVE : '']"
                aria-label="Liste à puces"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="size-4" aria-hidden="true" />
            </button>
            <button
                type="button"
                :class="[TOOLBAR_BUTTON, editor.isActive('orderedList') ? TOOLBAR_BUTTON_ACTIVE : '']"
                aria-label="Liste numérotée"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="size-4" aria-hidden="true" />
            </button>
            <button
                type="button"
                :class="[TOOLBAR_BUTTON, editor.isActive('blockquote') ? TOOLBAR_BUTTON_ACTIVE : '']"
                aria-label="Citation"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                <Quote class="size-4" aria-hidden="true" />
            </button>
            <button
                type="button"
                :class="[TOOLBAR_BUTTON, editor.isActive('link') ? TOOLBAR_BUTTON_ACTIVE : '']"
                aria-label="Lien"
                @click="setLink"
            >
                <Link2 class="size-4" aria-hidden="true" />
            </button>

            <span class="mx-1 h-5 w-px bg-sand-200" aria-hidden="true" />

            <button
                type="button"
                :class="TOOLBAR_BUTTON"
                :disabled="!editor.can().undo()"
                aria-label="Annuler"
                @click="editor.chain().focus().undo().run()"
            >
                <Undo2 class="size-4" aria-hidden="true" />
            </button>
            <button
                type="button"
                :class="TOOLBAR_BUTTON"
                :disabled="!editor.can().redo()"
                aria-label="Rétablir"
                @click="editor.chain().focus().redo().run()"
            >
                <Redo2 class="size-4" aria-hidden="true" />
            </button>
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>
