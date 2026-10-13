import { ref } from 'vue';

/**
 * Repli lorsqu'une image référencée en base n'est plus servie (fichier supprimé du disque,
 * déploiement partiel, chemin devenu invalide).
 *
 * Sans ce garde-fou, le navigateur affiche sa propre icône d'image cassée : un rectangle gris
 * avec une croix, parfaitement hors registre sur un site institutionnel, et répété autant de
 * fois qu'il y a d'entrées dans la liste. Avec lui, l'entrée retombe sur le même emplacement
 * sobre que celui prévu pour un article sans couverture — l'utilisateur ne voit pas la panne.
 *
 * Volontairement côté client : seul le navigateur sait qu'une URL n'a pas répondu, et vérifier
 * l'existence de chaque fichier côté serveur coûterait un accès disque par ligne de liste.
 *
 * Usage :
 *   const { isBroken, markBroken } = useBrokenImages();
 *   <img v-if="a.image && !isBroken(a.slug)" :src="a.image" @error="markBroken(a.slug)" />
 */
export function useBrokenImages() {
    const broken = ref(new Set<string>());

    return {
        isBroken: (key: string): boolean => broken.value.has(key),
        markBroken: (key: string): void => {
            // Nouveau Set plutôt que `.add()` : un Set muté en place ne déclenche pas le
            // rendu, Vue ne traçant pas les méthodes de collection sur un `ref` de cette façon.
            broken.value = new Set(broken.value).add(key);
        },
    };
}
