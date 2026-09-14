import { z } from 'zod';

/**
 * Schémas de validation du wizard d'adhésion (CLAUDE.md §5), un par étape. Utilisés avec
 * vee-validate (`toTypedSchema`) côté client — les règles équivalentes existent aussi côté
 * serveur dans `StoreMemberRequest` (impossible de littéralement partager zod/PHP, mais les
 * règles doivent rester alignées ; la validation serveur fait foi, celle-ci n'est qu'un
 * confort d'UX — cf. CLAUDE.md §8, jamais faire confiance au seul client).
 */

// Numéro mobile sénégalais : 9 chiffres commençant par 7 (indicatif +221 géré séparément).
const SENEGAL_MOBILE_REGEX = /^7\d{8}$/;

// Pièce d'identité (CNI ou passeport) : alphanumérique, tirets/espaces tolérés, 5 à 20
// caractères — pas de format unique imposé (les deux documents diffèrent), juste une
// hygiène minimale des données saisies.
const ID_DOCUMENT_REGEX = /^[A-Za-z0-9][A-Za-z0-9 -]{3,18}[A-Za-z0-9]$/;

export const identiteSchema = z.object({
    firstName: z.string().min(1, 'Le prénom est requis.'),
    lastName: z.string().min(1, 'Le nom est requis.'),
    birthDate: z.string().optional(),
    gender: z.enum(['homme', 'femme'], { message: 'Sélectionnez une option.' }),
    phone: z
        .string()
        .min(1, 'Le numéro de téléphone est requis.')
        .refine((value) => SENEGAL_MOBILE_REGEX.test(value.replace(/\s+/g, '')), {
            message: 'Numéro sénégalais invalide (9 chiffres commençant par 7, ex. 77 543 60 81).',
        }),
    email: z.string().min(1, "L'email est requis.").email('Adresse email invalide.'),
    region: z.string().min(1, 'Sélectionnez votre région.'),
    commune: z.string().min(1, 'La commune est requise.'),
    address: z.string().optional(),
    profession: z.string().optional(),
    idDocumentNumber: z
        .string()
        .optional()
        .refine((value) => !value || ID_DOCUMENT_REGEX.test(value), {
            message: 'Numéro de pièce invalide (5 à 20 caractères alphanumériques).',
        }),
});

export const engagementSchema = z.object({
    section: z.enum(['standard', 'jeunes', 'femmes']),
    referralSource: z.string().optional(),
    consent: z
        .boolean()
        .refine((value) => value === true, {
            message: "Vous devez accepter les statuts et la politique de confidentialité pour continuer.",
        }),
});

export type IdentiteForm = z.infer<typeof identiteSchema>;
export type EngagementForm = z.infer<typeof engagementSchema>;
