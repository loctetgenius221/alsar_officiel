<?php

namespace App\Domain\Content;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Source de vérité unique pour le contenu éditable des pages vitrine : décrit, par slug de
 * page puis par section puis par champ, le type et les règles de validation. Ce schéma pilote
 * à la fois les règles serveur (validationRules) et l'écran d'édition générique côté admin
 * (Pages/Admin/PageContent/Edit.vue reçoit ce schéma tel quel en prop) — CLAUDE.md exige "un
 * éditeur de blocs simple... juste des champs structurés par section", pas un page-builder.
 *
 * 6 types de champs seulement : text, textarea, richtext, image, select_icon, repeater.
 */
class PageSectionSchemas
{
    public static function all(): array
    {
        return [
            'home' => 'Accueil',
            'le-parti' => 'Le Parti',
            'le-president' => 'Le Président',
            'programme' => 'Programme',
            'regions' => 'Régions',
            'mediatheque' => 'Médiathèque',
            'site-settings' => 'En-tête & pied de page',
        ];
    }

    public static function for(string $slug): array
    {
        return match ($slug) {
            'home' => self::home(),
            'le-parti' => self::leParti(),
            'le-president' => self::lePresident(),
            'programme' => self::programme(),
            'regions' => self::regions(),
            'mediatheque' => self::mediatheque(),
            'site-settings' => self::siteSettings(),
            default => throw new InvalidArgumentException("Page de contenu inconnue : {$slug}"),
        };
    }

    /**
     * Règles de validation Laravel en notation pointée, ex. 'sections.hero.headline' => [...],
     * 'sections.hero.slides' => ['array','max:8'], 'sections.hero.slides.*.image.file' => [...].
     */
    public static function validationRules(string $slug): array
    {
        $rules = [];
        foreach (self::for($slug) as $sectionKey => $section) {
            foreach ($section['fields'] as $fieldKey => $field) {
                self::collectRules($rules, "sections.{$sectionKey}.{$fieldKey}", $field);
            }
        }

        return $rules;
    }

    /**
     * Libellés lisibles de chaque chemin validé, en miroir de validationRules() — ex.
     * 'sections.hero.slides.*.image.file' => 'Héros → Diaporama → Image'. Sans ça, un message
     * d'erreur afficherait le chemin technique brut à un utilisateur non développeur.
     * Consommé par UpdatePageContentRequest::attributes().
     */
    public static function attributeLabels(string $slug): array
    {
        $labels = [];
        foreach (self::for($slug) as $sectionKey => $section) {
            foreach ($section['fields'] as $fieldKey => $field) {
                self::collectLabels($labels, "sections.{$sectionKey}.{$fieldKey}", $section['label'], $field);
            }
        }

        return $labels;
    }

    /**
     * Remplace chaque champ `image` ({path, alt}) par ({url, alt}) pour l'affichage public ET
     * pour les aperçus de l'écran d'édition admin — parcourt le schéma (pas le JSON brut) pour
     * savoir exactement où se trouvent les champs image, y compris imbriqués dans un répéteur.
     */
    public static function resolveImagesForDisplay(string $slug, array $sections): array
    {
        foreach (self::for($slug) as $sectionKey => $section) {
            if (! isset($sections[$sectionKey])) {
                continue;
            }
            $sections[$sectionKey] = self::resolveFields($section['fields'], $sections[$sectionKey]);
        }

        return $sections;
    }

    /**
     * Chemins (relatifs au disque `public`) de toutes les images actuellement référencées par
     * `sections`, utilisé pour le nettoyage des fichiers orphelins après mise à jour.
     */
    public static function collectImagePaths(string $slug, array $sections): array
    {
        $paths = [];
        foreach (self::for($slug) as $sectionKey => $section) {
            if (isset($sections[$sectionKey])) {
                self::walkImagePaths($section['fields'], $sections[$sectionKey], $paths);
            }
        }

        return array_values(array_filter(array_unique($paths)));
    }

    private static function collectRules(array &$rules, string $path, array $field): void
    {
        if ($field['type'] === 'repeater') {
            // Pas de contrainte de nombre de lignes (min/max) : demande explicite de suppression
            // des validations de confort. `array` reste un simple garde-fou de type (empêche un
            // TypeError si jamais une chaîne était soumise à la place d'une liste), pas une
            // restriction de contenu. Les répéteurs verrouillés (nav_items, axes du Programme)
            // le restent visuellement dans l'admin via allowReorder/allowRemove — ce verrou est
            // indépendant de cette validation et n'est pas touché ici.
            $rules[$path] = ['array'];
            foreach ($field['fields'] as $subKey => $subField) {
                self::collectRules($rules, "{$path}.*.{$subKey}", $subField);
            }

            return;
        }

        if ($field['type'] === 'image') {
            $mimes = $field['mimes'] ?? 'jpg,jpeg,png,webp,gif,bmp';
            $isVideo = str_contains($mimes, 'mp4') || str_contains($mimes, 'webm');
            $rules["{$path}.path"] = ['nullable', 'string'];
            $rules["{$path}.alt"] = ['nullable', 'string'];
            $rules["{$path}.remove"] = ['nullable', 'boolean'];
            // Le fichier reste vérifié comme un vrai fichier de ce type (sécurité : empêche un
            // exécutable déguisé en média) mais sans plafond de taille serré — demande explicite
            // de suppression des validations de confort, seule la vérification de type reste.
            $rules["{$path}.file"] = ['nullable', 'file', "mimes:{$mimes}", $isVideo ? 'max:102400' : 'max:20480'];

            return;
        }

        $rules[$path] = $field['rules'];
    }

    /**
     * Pendant exact de collectRules() : même parcours récursif, mais qui accumule un libellé
     * « fil d'Ariane » (« Section → Répéteur → Champ ») au lieu de règles de validation.
     */
    private static function collectLabels(array &$labels, string $path, string $prefix, array $field): void
    {
        $label = trim("{$prefix} → {$field['label']}", ' →');

        if ($field['type'] === 'repeater') {
            $labels[$path] = $label;
            foreach ($field['fields'] as $subKey => $subField) {
                self::collectLabels($labels, "{$path}.*.{$subKey}", $label, $subField);
            }

            return;
        }

        if ($field['type'] === 'image') {
            // `.file` est le seul de ces quatre chemins qu'un utilisateur reconnaît comme
            // « le fichier que je viens de choisir » — les autres sont des détails de
            // transport, autant leur donner un nom explicite plutôt qu'un chemin brut.
            $labels["{$path}.file"] = $label;
            $labels["{$path}.alt"] = "{$label} (description de l'image)";
            $labels["{$path}.path"] = $label;
            $labels["{$path}.remove"] = $label;

            return;
        }

        $labels[$path] = $label;
    }

    private static function resolveFields(array $fields, array $values): array
    {
        foreach ($fields as $key => $field) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            if ($field['type'] === 'image') {
                $path = $values[$key]['path'] ?? null;
                $values[$key] = [
                    'path' => $path,
                    'url' => $path ? self::resolveUrl($path) : null,
                    'alt' => $values[$key]['alt'] ?? '',
                ];
            } elseif ($field['type'] === 'repeater') {
                $values[$key] = array_map(
                    fn (array $item) => self::resolveFields($field['fields'], $item),
                    $values[$key] ?? [],
                );
            }
        }

        return $values;
    }

    private static function walkImagePaths(array $fields, array $values, array &$paths): void
    {
        foreach ($fields as $key => $field) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            if ($field['type'] === 'image') {
                $paths[] = $values[$key]['path'] ?? null;
            } elseif ($field['type'] === 'repeater') {
                foreach ($values[$key] ?? [] as $item) {
                    self::walkImagePaths($field['fields'], $item, $paths);
                }
            }
        }
    }

    /**
     * Chemins déjà absolus (assets statiques hérités, ex. `/images/logo-alsar.png`) passent
     * inchangés ; les chemins relatifs au disque `public` (uploadés via l'admin) sont résolus
     * via Storage::url(). Permet au seeder de pointer certains champs vers des assets publics
     * existants sans forcer une copie immédiate.
     */
    private static function resolveUrl(string $path): string
    {
        return str_starts_with($path, '/') ? $path : Storage::disk('public')->url($path);
    }

    // --- Types de champs -------------------------------------------------------------------

    /**
     * `$required`/`$max` restent acceptés en paramètres (tous les appels du fichier les
     * renseignent déjà, ex. `max: 150`) pour ne pas devoir toucher chaque appel, mais ne sont
     * plus traduits en règles bloquantes : demande explicite de suppression des validations de
     * confort sur le contenu édité depuis le back-office. Le type `string` reste vérifié — pas
     * une contrainte de confort, juste un garde-fou minimal qui évite un TypeError plus loin
     * si jamais un tableau/objet était soumis à la place d'un texte.
     */
    private static function text(string $label, bool $required = true, int $max = 255): array
    {
        return ['type' => 'text', 'label' => $label, 'rules' => ['nullable', 'string']];
    }

    private static function textarea(string $label, bool $required = true, int $max = 2000): array
    {
        return ['type' => 'textarea', 'label' => $label, 'rules' => ['nullable', 'string']];
    }

    private static function richtext(string $label, bool $required = false): array
    {
        return ['type' => 'richtext', 'label' => $label, 'rules' => ['nullable', 'string']];
    }

    private static function image(string $label, string $mimes = 'jpg,jpeg,png,webp,gif,bmp'): array
    {
        return ['type' => 'image', 'label' => $label, 'mimes' => $mimes];
    }

    /**
     * Liste blanche d'icônes retirée de la validation serveur (demande explicite) — le
     * `<select>` de l'admin continue de ne proposer que des icônes réelles, donc ce
     * relâchement ne change rien en usage normal ; seul un appel API direct pourrait
     * enregistrer un nom sans composant correspondant, auquel cas `resolveIcon()` (front)
     * retombe déjà sur une icône par défaut plutôt que de planter.
     */
    private static function selectIcon(string $label): array
    {
        return [
            'type' => 'select_icon',
            'label' => $label,
            'rules' => ['nullable', 'string'],
        ];
    }

    private static function repeater(
        string $label,
        array $fields,
        int $min = 0,
        ?int $max = null,
        bool $allowReorder = true,
        bool $allowRemove = true,
        string $itemLabelField = '',
    ): array {
        return [
            'type' => 'repeater',
            'label' => $label,
            'fields' => $fields,
            'min' => $min,
            'max' => $max,
            'allowReorder' => $allowReorder,
            'allowRemove' => $allowRemove,
            'itemLabelField' => $itemLabelField,
        ];
    }

    private static function pageHeaderSection(string $defaultKicker = 'kicker'): array
    {
        return [
            'label' => 'En-tête de page',
            'fields' => [
                'kicker' => self::text('Kicker (petit texte au-dessus du titre)', max: 60),
                'title' => self::text('Titre', max: 150),
                'intro' => self::textarea('Introduction', max: 400),
                'image' => self::image('Photo de fond'),
            ],
        ];
    }

    // --- Pages -------------------------------------------------------------------------------

    private static function home(): array
    {
        return [
            'hero' => [
                'label' => 'Hero (bandeau plein cadre)',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 150),
                    'headline' => self::text('Titre principal', max: 150),
                    'body' => self::textarea('Paragraphe', max: 500),
                    'cta_primary_label' => self::text('Libellé du bouton principal', max: 40),
                    'cta_secondary_label' => self::text('Libellé du bouton secondaire', max: 40),
                    'slides' => self::repeater('Photos du carrousel', [
                        'image' => self::image('Photo'),
                    ], min: 1, max: 8),
                ],
            ],
            'president_teaser' => [
                'label' => 'Bloc "Le Président"',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'quote' => self::textarea('Citation', max: 300),
                    'body' => self::textarea('Paragraphe', max: 600),
                    'badges' => self::repeater('Repères courts', [
                        'text' => self::text('Texte', max: 80),
                    ], min: 1, max: 5),
                    'cta_label' => self::text('Libellé du lien', max: 40),
                    'portrait' => self::image('Portrait'),
                ],
            ],
            'impact_stats' => [
                'label' => 'Bilan & trajectoire (chiffres clés)',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'intro' => self::textarea('Paragraphe', max: 400),
                    'video' => self::image('Vidéo (mp4/webm)', mimes: 'mp4,webm'),
                    'stats' => self::repeater('Chiffres clés', [
                        'mode' => [
                            'type' => 'text',
                            'label' => 'Mode (count_up_year, count_up_number ou static)',
                            'rules' => ['nullable', 'string'],
                        ],
                        'value' => self::text('Valeur (année de départ, cible du compteur, ou texte statique)', max: 20),
                        'label' => self::textarea('Légende', max: 200),
                    ], min: 3, max: 3, allowReorder: false, allowRemove: false),
                ],
            ],
            'program_pillars' => [
                'label' => 'Teaser Programme (liste d\'axes)',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'intro' => self::textarea('Chapô sous le titre (facultatif)', max: 300),
                    'cta_label' => self::text('Libellé du lien "Voir les 6 axes"', max: 60),
                    'pillars' => self::repeater('Axes mis en avant', [
                        'id' => [
                            'type' => 'text',
                            'label' => 'Ancre (doit correspondre à l\'id d\'un axe de la page Programme)',
                            'rules' => ['nullable', 'string'],
                        ],
                        'icon' => self::selectIcon('Icône'),
                        'title' => self::text('Titre', max: 150),
                        'text' => self::textarea('Texte', max: 400),
                        'image' => self::image('Photo'),
                    ], min: 1, max: 6),
                ],
            ],
            'regions_teaser' => [
                'label' => 'Teaser Régions',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'body' => self::textarea('Paragraphe', max: 400),
                    'map_caption' => self::text('Légende sous la carte', max: 150),
                ],
            ],
            'join_cta' => [
                'label' => 'Bloc "Rejoignez-nous"',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'body' => self::textarea('Paragraphe', max: 400),
                    'cta_label' => self::text('Libellé du bouton', max: 40),
                    'photos' => self::repeater('Photos', [
                        'image' => self::image('Photo'),
                    ], min: 1, max: 6),
                ],
            ],
            'brand_ticker' => [
                'label' => 'Bandeau défilant',
                'fields' => [
                    'phrases' => self::repeater('Phrases', [
                        'text' => self::text('Texte', max: 60),
                    ], min: 1, max: 10),
                ],
            ],
        ];
    }

    private static function leParti(): array
    {
        return [
            'header' => self::pageHeaderSection(),
            'vision' => [
                'label' => 'Vision',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'body_intro' => self::textarea('Premier paragraphe', max: 400),
                    'body_detail' => self::richtext('Second paragraphe (gras autorisé)'),
                    'values' => self::repeater('Valeurs', [
                        'label' => self::text('Libellé', max: 100),
                        'text' => self::textarea('Description', max: 200),
                    ], min: 1, max: 8),
                ],
            ],
            'histoire' => [
                'label' => 'Histoire (frise chronologique)',
                'fields' => [
                    'title' => self::text('Titre de la section', max: 100),
                    'chronology' => self::repeater('Étapes', [
                        'date' => self::text('Date', max: 40),
                        'text' => self::textarea('Texte', max: 500),
                    ], min: 1, max: 12),
                    'image' => self::image('Photo d\'illustration'),
                ],
            ],
            'organisation' => [
                'label' => 'Organisation',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'facts' => self::repeater('Repères', [
                        'label' => self::text('Libellé', max: 100),
                        'value' => self::text('Valeur', max: 150),
                    ], min: 0, max: 6),
                    'note' => self::textarea('Note', max: 500),
                ],
            ],
            'statuts' => [
                'label' => 'Statuts & Transparence',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'coming_soon_text' => self::textarea('Texte "à venir"', max: 500),
                ],
            ],
        ];
    }

    private static function lePresident(): array
    {
        return [
            'header' => self::pageHeaderSection(),
            'portrait' => [
                'label' => 'Portrait',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'body' => self::textarea('Paragraphe', max: 900),
                    'facts' => self::repeater('Repères chiffrés', [
                        'mode' => [
                            'type' => 'text',
                            'label' => 'Mode (count_up_year ou static)',
                            'rules' => ['nullable', 'string'],
                        ],
                        'value' => self::text('Valeur (année de départ, ou texte statique)', max: 20),
                        'label' => self::text('Légende', max: 100),
                    ], min: 3, max: 3, allowReorder: false, allowRemove: false),
                    'image' => self::image('Portrait'),
                ],
            ],
            'realisations' => [
                'label' => 'Réalisations à Podor',
                'fields' => [
                    'title' => self::text('Titre', max: 150),
                    'body' => self::textarea('Paragraphe', max: 600),
                ],
            ],
            'parcours' => [
                'label' => 'Parcours (frise chronologique)',
                'fields' => [
                    'title' => self::text('Titre de la section', max: 100),
                    'career' => self::repeater('Étapes', [
                        'date' => self::text('Date', max: 40),
                        'text' => self::textarea('Texte', max: 600),
                    ], min: 1, max: 15),
                    'image' => self::image('Photo d\'illustration'),
                ],
            ],
            'discours' => [
                'label' => 'Discours & Revue de presse',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'press_outlets' => self::repeater('Médias', [
                        'text' => self::text('Nom du média', max: 60),
                    ], min: 0, max: 8),
                    'coming_soon_text' => self::textarea('Texte "à venir"', max: 500),
                ],
            ],
        ];
    }

    private static function programme(): array
    {
        return [
            'header' => self::pageHeaderSection(),
            'intro' => [
                'label' => 'Note d\'introduction',
                'fields' => [
                    'text' => self::textarea('Texte', max: 400),
                ],
            ],
            'axes' => [
                'label' => 'Les 6 axes du programme',
                'fields' => [
                    'axes' => self::repeater('Axes', [
                        'id' => [
                            'type' => 'text',
                            'label' => 'Ancre (identifiant technique, minuscules et tirets)',
                            'rules' => ['nullable', 'string'],
                        ],
                        'icon' => self::selectIcon('Icône'),
                        'title' => self::text('Titre', max: 150),
                        'text' => self::textarea('Texte', max: 500),
                    ], min: 6, max: 6, allowReorder: false, allowRemove: false),
                ],
            ],
        ];
    }

    private static function regions(): array
    {
        return [
            'header' => self::pageHeaderSection(),
            'map' => [
                'label' => 'Carte',
                'fields' => [
                    'caption' => self::text('Légende sous la carte', max: 150),
                ],
            ],
            'region_info' => [
                'label' => 'Coordinations locales identifiées',
                'fields' => [
                    'regions' => self::repeater('Régions', [
                        'region_name' => [
                            'type' => 'text',
                            'label' => 'Région (doit correspondre exactement à un nom de région)',
                            'rules' => ['nullable', 'string'],
                        ],
                        'section' => self::text('Nom de la section/coordination', max: 100),
                        'contact' => self::text('Responsable local·e', required: false, max: 100),
                        'note' => self::textarea('Note', max: 400),
                    ], min: 0, max: 14),
                ],
            ],
            'default_note' => [
                'label' => 'Note par défaut (régions sans coordination détaillée)',
                'fields' => [
                    'text' => self::textarea('Texte', max: 500),
                ],
            ],
            'sections_locales' => [
                'label' => 'Bloc "Sections locales"',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                ],
            ],
        ];
    }

    private static function mediatheque(): array
    {
        return [
            'header' => self::pageHeaderSection(),
            'gallery' => [
                'label' => 'Galerie photo',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'intro' => self::textarea('Paragraphe', max: 300),
                    'photos' => self::repeater('Photos', [
                        'image' => self::image('Photo'),
                        'caption' => self::text('Légende (optionnelle)', required: false, max: 150),
                    ], min: 1, max: 20),
                ],
            ],
            'video' => [
                'label' => 'Vidéo',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'body' => self::textarea('Paragraphe', max: 300),
                ],
            ],
            'podcasts' => [
                'label' => 'Podcasts & interviews',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'coming_soon_text' => self::textarea('Texte "à venir"', max: 500),
                ],
            ],
            'presse' => [
                'label' => 'Espace presse',
                'fields' => [
                    'kicker' => self::text('Kicker', max: 60),
                    'title' => self::text('Titre', max: 150),
                    'body' => self::textarea('Paragraphe', max: 400),
                ],
            ],
        ];
    }

    private static function siteSettings(): array
    {
        return [
            'header' => [
                'label' => 'En-tête du site',
                'fields' => [
                    'brand_text' => self::text('Nom affiché à côté du logo', max: 40),
                    'logo' => self::image('Logo'),
                    'nav_items' => self::repeater(
                        'Libellés du menu (ordre et nombre fixes — seuls les libellés sont modifiables)',
                        ['label' => self::text('Libellé', max: 30)],
                        min: 6,
                        max: 6,
                        allowReorder: false,
                        allowRemove: false,
                    ),
                    'cta_label' => self::text('Libellé du bouton "Adhérer"', max: 30),
                ],
            ],
            'footer' => [
                'label' => 'Pied de page',
                'fields' => [
                    'brand_text' => self::text('Nom affiché à côté du logo', max: 40),
                    'logo' => self::image('Logo'),
                    'tagline' => self::textarea('Texte sous le logo', max: 200),
                    'contact_email' => self::text('Email de contact', max: 100),
                    'contact_address' => self::text('Adresse', max: 200),
                    'facebook_url' => self::text('URL de la page Facebook (laisser vide si aucune)', required: false, max: 255),
                    'copyright_text' => self::text('Texte de copyright (après "© {année} ")', max: 100),
                ],
            ],
        ];
    }
}
