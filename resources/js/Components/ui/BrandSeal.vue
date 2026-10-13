<script setup lang="ts">
/**
 * Sceau circulaire : devise du parti courant autour d'un anneau, logo au centre.
 *
 * Posé UNE SEULE FOIS sur le site, au moment de l'adhésion. C'est tout l'intérêt d'un sceau :
 * répété, il devient un ornement ; unique, il signe.
 *
 * Volontairement STATIQUE. L'anneau tournant est une signature d'agence : il attire l'œil sur
 * lui-même, vieillit vite, et dit « tendance » là où un parti politique doit dire « officiel »
 * (CLAUDE.md §3 : sobriété premium > effets gratuits). Un sceau immobile se lit comme un cachet
 * apposé sur un document.
 *
 * DEUX ARCS et non un cercle unique. Avec un seul cercle porteur, la moitié basse du texte sort
 * à l'envers (le glyphe est posé perpendiculairement à la trajectoire, qui s'inverse en bas), et
 * une devise répétée pour « faire le tour » ne retombe jamais juste : elle se chevauche. Ici,
 * l'arc haut court dans le sens horaire et l'arc bas dans le sens inverse — les deux textes sont
 * donc droits, chacun centré sur son arc.
 *
 * Les rayons (72 en haut, 82 en bas) ne sont pas une coquille : les glyphes se développent
 * toujours vers l'intérieur de la courbe, donc vers le haut sur l'arc supérieur et vers le bas
 * sur l'arc inférieur. Ces deux valeurs placent les deux textes dans la même bande optique,
 * autour du rayon 77.
 *
 * Décoratif : `aria-hidden`, pas de texte accessible — la devise est déjà écrite en toutes
 * lettres dans le titre de la section, l'annoncer deux fois n'apporterait rien.
 */
withDefaults(
    defineProps<{
        /** Arc supérieur, lu dans le sens de lecture normal. */
        top?: string;
        /** Arc inférieur. Court, pour rester lisible à cette taille. */
        bottom?: string;
    }>(),
    {
        top: 'LE TRAVAIL ET LA RACINE',
        // Pas « ALSAR » ici : le logo au centre le dit déjà, et un sceau qui répète deux fois
        // le même mot gaspille son seul espace utile. Le siège est une information, lui.
        bottom: 'DAKAR · SÉNÉGAL',
    },
);
</script>

<template>
    <!-- La racine ne porte NI largeur NI positionnement : ce sont les deux choses que
         l'appelant fixe (`absolute top-8 right-8 w-20`). Les classes passées de l'extérieur
         atterrissent sur ce même élément, à spécificité égale — un `w-full`/`relative` déclaré
         ici entrerait en conflit avec le `w-20`/`absolute` du parent, et c'est l'ordre de la
         feuille de style qui trancherait : le sceau s'affichait tantôt à la bonne taille,
         tantôt pleine largeur et replacé dans le flux. Le `relative` dont le logo a besoin vit
         donc sur un conteneur interne. -->
    <div class="aspect-square" aria-hidden="true">
        <div class="relative h-full w-full">
            <svg viewBox="0 0 200 200" class="h-full w-full text-gold-500">
                <defs>
                    <path id="alsar-seal-top" d="M 100,100 m -72,0 a 72,72 0 0 1 144,0" fill="none" />
                    <path id="alsar-seal-bottom" d="M 100,100 m -82,0 a 82,82 0 0 0 164,0" fill="none" />
                </defs>

                <!-- Deux filets fins plutôt qu'un cercle épais : registre de cachet gravé, pas de
                 pastille. -->
                <circle
                    cx="100"
                    cy="100"
                    r="90"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="0.75"
                    opacity="0.5"
                />
                <circle
                    cx="100"
                    cy="100"
                    r="62"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="0.75"
                    opacity="0.35"
                />

                <text class="font-sans" fill="currentColor" font-size="8.5" letter-spacing="2.2">
                    <textPath href="#alsar-seal-top" startOffset="50%" text-anchor="middle">
                        {{ top }}
                    </textPath>
                </text>
                <text class="font-sans" fill="currentColor" font-size="8.5" letter-spacing="2.2">
                    <textPath href="#alsar-seal-bottom" startOffset="50%" text-anchor="middle">
                        {{ bottom }}
                    </textPath>
                </text>

                <!-- Losanges aux deux flancs : ils ferment la composition là où les deux arcs se
                 rejoignent, et évitent que les textes paraissent flotter. -->
                <path d="M 12,100 L 16,96 L 20,100 L 16,104 Z" fill="currentColor" opacity="0.6" />
                <path d="M 180,100 L 184,96 L 188,100 L 184,104 Z" fill="currentColor" opacity="0.6" />
            </svg>

            <!-- Logo au centre, en dehors du SVG : un <image> SVG ne gère ni le cache ni le
             chargement différé aussi bien qu'une vraie balise img. -->
            <img
                src="/images/logo-alsar.png"
                alt=""
                class="absolute top-1/2 left-1/2 w-[26%] -translate-x-1/2 -translate-y-1/2 object-contain"
                loading="lazy"
            />
        </div>
    </div>
</template>
