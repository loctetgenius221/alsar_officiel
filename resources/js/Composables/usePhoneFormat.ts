/**
 * Formatage léger d'un numéro sénégalais (+221, groupes 2-3-2-2 : "77 543 60 81").
 * Le préfixe est éditable pour rester ouvert à la diaspora (CLAUDE.md §5).
 * Découpage par positions fixes (pas une regex à pas de 2) : sur 9 chiffres, un pas de 2
 * uniforme laisse un chiffre isolé en bout de numéro ("77 98 76 54 3") — bug constaté sur
 * une vraie carte générée, corrigé ici plutôt qu'au niveau de l'affichage seul, puisque
 * cette chaîne déjà espacée est ensuite stockée telle quelle en base (StepIdentite.vue).
 */
export function formatSenegalesePhone(value: string): string {
    const digits = value.replace(/\D/g, '').slice(0, 9);
    return [digits.slice(0, 2), digits.slice(2, 5), digits.slice(5, 7), digits.slice(7, 9)]
        .filter(Boolean)
        .join(' ');
}
