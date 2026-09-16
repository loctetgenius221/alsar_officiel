import { describe, expect, it, vi } from 'vitest';
import { containsFile, patchWithFiles } from './inertia-upload';

/**
 * Ces tests verrouillent la correction du bug d'envoi d'image décrit en tête de
 * `inertia-upload.ts` : un PATCH multipart arrive vide côté PHP, il faut donc passer par
 * POST + `_method`. Aucun test PHP ne peut couvrir ce point — le client HTTP de Pest
 * construit la Request en mémoire sans jamais passer par le décodage multipart de PHP.
 */

/** Faux `useForm` Inertia, réduit à ce dont le helper a besoin. */
function fakeForm(data: Record<string, unknown>) {
    const form = {
        data: () => data,
        transform: vi.fn(function (this: unknown, callback: (d: unknown) => unknown) {
            form.transformCallback = callback;

            return form;
        }),
        patch: vi.fn(),
        post: vi.fn(),
        transformCallback: (d: unknown) => d,
    };

    return form;
}

describe('containsFile', () => {
    it('détecte un fichier à la racine', () => {
        expect(containsFile({ photo: new File([''], 'a.jpg') })).toBe(true);
    });

    it("détecte un fichier au fond d'une structure imbriquée (répéteur de photos)", () => {
        const data = {
            sections: {
                galerie: {
                    photos: [{ image: { file: null } }, { image: { file: new File([''], 'b.jpg') } }],
                },
            },
        };

        expect(containsFile(data)).toBe(true);
    });

    it('renvoie faux quand aucun fichier n’est présent', () => {
        const data = {
            sections: { hero: { titre: 'Bonjour', image: { path: 'x.jpg', file: null, remove: false } } },
        };

        expect(containsFile(data)).toBe(false);
    });
});

describe('patchWithFiles', () => {
    it('envoie en POST avec _method quand un fichier est joint', () => {
        const form = fakeForm({ sections: { hero: { image: { file: new File([''], 'c.jpg') } } } });

        patchWithFiles(form as never, '/admin/contenu-des-pages/le-parti');

        // Le point critique : POST (seul verbe que PHP décode en multipart), pas PATCH.
        expect(form.post).toHaveBeenCalledWith('/admin/contenu-des-pages/le-parti', undefined);
        expect(form.patch).not.toHaveBeenCalled();
        expect(form.transformCallback({ a: 1 })).toEqual({ a: 1, _method: 'patch' });
    });

    it('reste en PATCH sans fichier, pour ne pas basculer inutilement en multipart', () => {
        // En multipart, `null` deviendrait `''`, que le serveur interprète comme « champ vidé
        // volontairement » : basculer sans raison risquerait d'effacer du contenu.
        const form = fakeForm({ sections: { hero: { titre: 'Bonjour' } } });

        patchWithFiles(form as never, '/admin/contenu-des-pages/le-parti');

        expect(form.patch).toHaveBeenCalledWith('/admin/contenu-des-pages/le-parti', undefined);
        expect(form.post).not.toHaveBeenCalled();
        expect(form.transformCallback({ a: 1 })).toEqual({ a: 1 });
    });

    it('réinitialise la transformation entre deux envois', () => {
        // Inertia conserve la dernière transformation posée : sans remise à zéro, un
        // enregistrement sans image hériterait du `_method` du précédent.
        const form = fakeForm({ image: new File([''], 'd.jpg') });
        patchWithFiles(form as never, '/url');
        expect(form.transformCallback({})).toEqual({ _method: 'patch' });

        const sansFichier = fakeForm({ titre: 'x' });
        patchWithFiles(sansFichier as never, '/url');
        expect(sansFichier.transformCallback({})).toEqual({});
    });
});
