/**
 * Jeton CSRF pour les appels `fetch` manuels (hors formulaires Inertia, qui le gèrent déjà
 * eux-mêmes) — lu depuis le cookie XSRF-TOKEN posé par Laravel, comme le fait axios en
 * interne. Nécessaire pour les vérifications live (ex. disponibilité d'un email).
 */
export function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}
