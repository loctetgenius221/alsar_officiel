/**
 * Retour utilisateur global sur les échanges avec le serveur.
 *
 * État précédent : les contrôleurs renvoyaient bien des messages (`back()->with('success', …)`)
 * mais une seule page les affichait — enregistrer un article, du contenu de page ou une note
 * ne produisait donc AUCUN signe visible. Et un échec de transport (session expirée, fichier
 * refusé par le serveur, coupure réseau, erreur 500) ne produisait rien non plus : le bouton
 * se réactivait, sans plus d'explication.
 *
 * Tout est centralisé ici, branché une fois dans app.ts : aucune page n'a à répéter cette
 * plomberie, et un nouveau contrôleur qui flashe un message est affiché correctement sans
 * ligne de Vue supplémentaire.
 */
import { router } from '@inertiajs/vue3';
import { notify } from '@/lib/notify';
import { errorCountLabel } from '@/lib/form-errors';

interface HttpMessage {
    title: string;
    description: string;
    action?: { label: string; onClick: () => void };
}

function isAdminArea(): boolean {
    return window.location.pathname.startsWith('/admin');
}

/**
 * Messages de transport, écrits pour quelqu'un qui ne connaît ni les codes HTTP ni le mot
 * « requête » : ce qui s'est passé, ce que ça implique pour son travail en cours, et le
 * geste suivant.
 */
function httpMessage(status: number | undefined): HttpMessage {
    switch (status) {
        case 401:
        case 419:
            return isAdminArea()
                ? {
                      title: 'Votre session a expiré',
                      description:
                          "Vous avez été déconnecté après une longue inactivité. Reconnectez-vous dans un nouvel onglet, puis revenez ici : votre saisie est toujours à l'écran et vous pourrez enregistrer.",
                      // Nouvel onglet, jamais un rechargement : recharger cette page ferait
                      // perdre tout ce qui n'a pas encore été enregistré.
                      action: {
                          label: 'Se reconnecter',
                          onClick: () => window.open('/admin/login', '_blank'),
                      },
                  }
                : {
                      title: 'Votre session a expiré',
                      description:
                          'Rechargez la page puis réessayez. Vos réponses sont conservées, vous ne recommencerez pas de zéro.',
                      action: { label: 'Recharger la page', onClick: () => window.location.reload() },
                  };

        case 403:
            return {
                title: "Vous n'avez pas les droits pour cette action",
                description: "Demandez à l'administrateur du site de vous donner l'accès nécessaire.",
            };

        case 404:
            return {
                title: 'Cet élément est introuvable',
                description:
                    'Il a probablement été supprimé entre-temps. Revenez à la liste pour voir son état actuel.',
            };

        case 413:
            return {
                title: 'Le fichier est trop lourd',
                description:
                    "Le serveur a refusé l'envoi avant même de le recevoir en entier. Choisissez une image plus légère (idéalement moins de 2 Mo) et réessayez.",
            };

        case 429:
            return {
                title: 'Trop de tentatives en peu de temps',
                description:
                    "Patientez une minute, puis réessayez — c'est une protection automatique du site.",
            };

        default:
            return {
                title: "L'enregistrement a échoué côté serveur",
                description:
                    "Rien n'a été modifié et votre saisie est toujours à l'écran. Réessayez dans un instant ; si le problème se répète, signalez-le à l'administrateur du site.",
            };
    }
}

export function registerInertiaFeedback(): void {
    // --- Messages flashés par les contrôleurs (`->with('success'|'error', …)`) ----------------
    router.on('success', (event) => {
        const flash = event.detail.page.props.flash;

        // Une requête qui aboutit rend caduc tout message d'échec encore à l'écran : sans ça,
        // « Formulaire incomplet » resterait affiché à côté de « Brouillon créé », et
        // l'utilisateur ne saurait plus lequel des deux reflète l'état réel.
        if (flash?.success) {
            notify.dismissTone('error');
            notify.success({ title: flash.success });
        }

        if (flash?.error) notify.error({ title: flash.error });
    });

    // --- Erreurs de validation (422) ----------------------------------------------------------
    // Le détail par champ reste affiché dans le formulaire (FormErrorSummary + message sous
    // chaque champ) ; ce toast sert uniquement à rendre l'échec impossible à manquer quand le
    // haut du formulaire est hors écran au moment du clic sur « Enregistrer ».
    router.on('error', (event) => {
        const messages = Object.values(event.detail.errors ?? {}).filter(Boolean);
        if (messages.length === 0) return;

        // Un seul problème : on reprend son message tel quel. Le résumé générique ne convient
        // pas partout — sur l'écran de connexion, « Formulaire incomplet » serait tout
        // simplement faux, le formulaire est rempli, ce sont les identifiants qui ne
        // correspondent pas.
        if (messages.length === 1) {
            notify.error({ title: messages[0] });

            return;
        }

        notify.error({
            title: `Formulaire incomplet — ${errorCountLabel(messages.length)}`,
            description:
                "Rien n'a été enregistré, mais rien n'est perdu non plus : les points à reprendre sont listés en haut du formulaire.",
        });
    });

    // --- Réponse non exploitable (500, 419, 413, page HTML au lieu d'Inertia…) -----------------
    router.on('httpException', (event) => {
        notify.error(httpMessage(event.detail.response?.status));

        // En développement, on laisse en plus la fenêtre de diagnostic d'Inertia s'ouvrir (la
        // trace Laravel y est lisible) ; en production elle n'apporterait rien à l'utilisateur,
        // le toast la remplace.
        if (import.meta.env.DEV) return;

        return false;
    });

    // --- Réseau injoignable --------------------------------------------------------------------
    router.on('networkError', () => {
        notify.error({
            title: 'Connexion interrompue',
            description:
                "Le site n'a pas pu joindre le serveur. Vérifiez votre connexion internet puis réessayez — votre saisie est toujours à l'écran.",
        });

        return false;
    });
}
