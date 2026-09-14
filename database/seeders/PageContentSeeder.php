<?php

namespace Database\Seeders;

use App\Domain\Content\Models\PageContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Peuple les 7 lignes de contenu (une par page + réglages header/footer) avec le contenu
 * actuellement codé en dur dans les composants Vue, copié verbatim — objectif : un déploiement
 * de cette fonctionnalité ne change RIEN visuellement tant qu'aucun admin n'a rien édité.
 * Copie aussi les images actuellement statiques (public/images/*) vers le disque `public`
 * managé (storage/app/public/pages/...) pour que tous les champs image suivent le même
 * contrat par la suite (chemin toujours relatif au disque, jamais de cas particulier).
 */
class PageContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPage('home', 'Accueil', $this->home());
        $this->seedPage('le-parti', 'Le Parti', $this->leParti());
        $this->seedPage('le-president', 'Le Président', $this->lePresident());
        $this->seedPage('programme', 'Programme', $this->programme());
        $this->seedPage('regions', 'Régions', $this->regions());
        $this->seedPage('mediatheque', 'Médiathèque', $this->mediatheque());
        $this->seedPage('site-settings', 'En-tête & pied de page', $this->siteSettings());
    }

    private function seedPage(string $slug, string $label, array $sections): void
    {
        PageContent::updateOrCreate(
            ['slug' => $slug],
            ['label' => $label, 'sections' => $sections, 'seo_title' => null, 'seo_description' => null],
        );
    }

    private function home(): array
    {
        $slug = 'home';

        return [
            'hero' => [
                'kicker' => 'Alliance pour la Synergie des Actions Républicaines (ALSAR)',
                'headline' => "La Force du Changement, l'énergie du Progrès.",
                'body' => "ALSAR est un parti politique sénégalais porté par une troisième voie pragmatique — entre opposition et pouvoir en place, tournée vers des résultats concrets pour le Sénégal.",
                'cta_primary_label' => 'Rejoindre ALSAR',
                'cta_secondary_label' => 'Découvrir le programme',
                'slides' => [
                    ['image' => $this->img($slug, 'hero', 'slides/0', '/images/slider-img-1.webp', 'ALSAR sur le terrain à Podor (photo 1)')],
                    ['image' => $this->img($slug, 'hero', 'slides/1', '/images/slider-img-2.webp', 'ALSAR sur le terrain à Podor (photo 2)')],
                    ['image' => $this->img($slug, 'hero', 'slides/2', '/images/slider-img-3.webp', 'ALSAR sur le terrain à Podor (photo 3)')],
                    ['image' => $this->img($slug, 'hero', 'slides/3', '/images/slider-img-4.webp', 'ALSAR sur le terrain à Podor (photo 4)')],
                    ['image' => $this->img($slug, 'hero', 'slides/4', '/images/slider-img-5.webp', 'ALSAR sur le terrain à Podor (photo 5)')],
                ],
            ],
            'president_teaser' => [
                'kicker' => 'Le Président',
                'quote' => "Un homme d'action, entre réussite entrepreneuriale, mandat municipal et engagement national.",
                'body' => "Originaire de Soueïma (Podor), Mamadou Racine Sy dirige la commune de Podor depuis janvier 2022 après un parcours d'entrepreneur hôtelier. Fondateur en 2014 du mouvement ALPAR à l'origine d'ALSAR, il porte aujourd'hui une troisième voie pragmatique pour le Sénégal.",
                'badges' => [
                    ['text' => 'Maire de Podor depuis 2022'],
                    ['text' => "Fondateur d'ALPAR (2014)"],
                    ['text' => 'Ancien PDG, King Fahd Palace'],
                ],
                'cta_label' => 'Découvrir son parcours',
                'portrait' => $this->img($slug, 'president_teaser', 'portrait', '/images/Photo-du-président-racine-sy-1.jpeg', "Mamadou Racine Sy, Président d'ALSAR"),
            ],
            'impact_stats' => [
                'kicker' => 'Bilan & trajectoire',
                'title' => 'Des engagements tenus, une trajectoire claire.',
                'intro' => "De la mairie de Podor à la scène nationale, ALSAR avance par des actes vérifiables plutôt que par des slogans.",
                'video' => $this->img($slug, 'impact_stats', 'video', '/videos/videoHero.mp4', 'ALSAR sur le terrain — mobilisation et action à Podor'),
                'stats' => [
                    ['mode' => 'count_up_year', 'value' => '2022', 'label' => 'ans à la tête de la mairie de Podor, élu en janvier 2022.'],
                    ['mode' => 'count_up_number', 'value' => '6', 'label' => "axes structurent le programme national, de l'emploi à la gouvernance."],
                    ['mode' => 'static', 'value' => '2026', 'label' => "reconnaissance officielle d'ALSAR (Alliance pour la Synergie des Actions Républicaines) comme parti politique, après plus d'une décennie de terrain depuis ALPAR (2014)."],
                ],
            ],
            'program_pillars' => [
                'kicker' => 'Le programme',
                'title' => 'Trois axes, parmi six, pour transformer le Sénégal.',
                'cta_label' => 'Voir les 6 axes du programme',
                'pillars' => [
                    [
                        'id' => 'emploi',
                        'icon' => 'Briefcase',
                        'title' => "Pouvoir d'achat et emploi",
                        'text' => "Création d'emplois, soutien à l'entrepreneuriat et allégement fiscal sur les produits de première nécessité.",
                        'image' => $this->img($slug, 'program_pillars', 'pillars/0', '/images/slider-img-2.webp'),
                    ],
                    [
                        'id' => 'alimentaire',
                        'icon' => 'Wheat',
                        'title' => 'Souveraineté alimentaire',
                        'text' => 'Modernisation du secteur agropastoral et protection des droits des agriculteurs et éleveurs.',
                        'image' => $this->img($slug, 'program_pillars', 'pillars/1', '/images/slider-img-3.webp'),
                    ],
                    [
                        'id' => 'gouvernance',
                        'icon' => 'ShieldCheck',
                        'title' => 'Transparence et bonne gouvernance',
                        'text' => 'Mécanismes de contrôle et de transparence dans la gestion des ressources publiques.',
                        'image' => $this->img($slug, 'program_pillars', 'pillars/2', '/images/slider-img-5.webp'),
                    ],
                ],
            ],
            'regions_teaser' => [
                'kicker' => 'Régions',
                'title' => '14 régions, une seule ambition nationale.',
                'body' => "ALSAR est présent dans les 14 régions du Sénégal. Les coordinations locales, à l'image d'ALSAR Sagatta, seront détaillées au fur et à mesure de leur structuration officielle.",
                'map_caption' => 'Dakar — siège officiel du parti (en or sur la carte)',
            ],
            'join_cta' => [
                'kicker' => 'Rejoignez-nous',
                'title' => 'Le travail et la racine — ensemble, pour le Sénégal.',
                'body' => 'Devenez adhérent, bénévole ou relais local. Adhésion en ligne, cotisation et carte de membre numérique incluses.',
                'cta_label' => 'Rejoindre ALSAR',
                'photos' => [
                    ['image' => $this->img($slug, 'join_cta', 'photos/0', '/images/slider-img-2.webp', 'ALSAR sur le terrain (photo 2)')],
                    ['image' => $this->img($slug, 'join_cta', 'photos/1', '/images/slider-img-3.webp', 'ALSAR sur le terrain (photo 3)')],
                    ['image' => $this->img($slug, 'join_cta', 'photos/2', '/images/slider-img-4.webp', 'ALSAR sur le terrain (photo 4)')],
                    ['image' => $this->img($slug, 'join_cta', 'photos/3', '/images/slider-img-5.webp', 'ALSAR sur le terrain (photo 5)')],
                ],
            ],
            'brand_ticker' => [
                'phrases' => [
                    ['text' => 'ALSAR'],
                    ['text' => 'La Force du Changement'],
                    ['text' => "L'Énergie du Progrès"],
                    ['text' => 'Travail & Mérite'],
                    ['text' => 'Solidarité'],
                    ['text' => 'Bonne Gouvernance'],
                ],
            ],
        ];
    }

    private function leParti(): array
    {
        $slug = 'le-parti';

        return [
            'header' => [
                'kicker' => 'Le Parti',
                'title' => "Vision, histoire et organisation d'ALSAR",
                'intro' => "D'un mouvement citoyen né à Podor à un parti politique national : notre positionnement, nos valeurs et la manière dont nous nous organisons.",
                'image' => $this->img($slug, 'header', 'image', '/images/slider-img-2.webp', 'ALSAR sur le terrain'),
            ],
            'vision' => [
                'kicker' => 'Vision',
                'title' => 'Un Sénégal prospère, équitable et solidaire.',
                'body_intro' => 'Construit par un développement inclusif et durable, en dehors des clivages classiques opposition/pouvoir.',
                'body_detail' => "ALSAR se définit comme une <strong>troisième voie</strong> : un pragmatisme orienté résultats plutôt qu'une ligne idéologique classique.",
                'values' => [
                    ['label' => 'Travail (Liggey) et mérite', 'text' => 'Le travail comme socle du progrès individuel et collectif.'],
                    ['label' => 'Solidarité et justice sociale', 'text' => 'Personne ne doit être laissé pour compte.'],
                    ['label' => 'Bonne gouvernance et transparence', 'text' => "Rendre compte de l'usage des ressources publiques."],
                    ['label' => 'Pragmatisme et efficacité', 'text' => 'Des résultats concrets plutôt que des postures.'],
                    ['label' => 'Unité nationale au-delà des clivages partisans', 'text' => "Rassembler au-delà de l'opposition et du pouvoir."],
                ],
            ],
            'histoire' => [
                'title' => 'Notre histoire',
                'chronology' => [
                    ['date' => 'Avant 2014', 'text' => "Mamadou Racine Sy se fait connaître comme entrepreneur hôtelier (PDG du King Fahd Palace) et figure du patronat touristique sénégalais."],
                    ['date' => '2014', 'text' => "Convaincu que le développement doit partir des territoires, Mamadou Racine Sy crée ALPAR (And Liggey Podor ak Racine), mouvement citoyen destiné à accompagner les initiatives de développement dans le département de Podor."],
                    ['date' => '2019', 'text' => "Face à l'adhésion grandissante des populations, ALPAR évolue pour devenir ALSAR (And Liggey Sénégal ak Racine), mouvement national porté par le travail, la solidarité, l'investissement productif et la bonne gouvernance."],
                    ['date' => 'Janvier 2022', 'text' => 'Élection de Mamadou Racine Sy à la mairie de Podor.'],
                    ['date' => '2024', 'text' => "Le mouvement ALSAR participe pour la première fois à une élection nationale, en présentant une liste indépendante aux élections législatives — nouvelle étape de son implantation politique."],
                    ['date' => '2026', 'text' => "Après plus d'une décennie d'implantation progressive commencée avec ALPAR, ALSAR obtient sa reconnaissance officielle comme parti politique sous l'appellation Alliance pour la Synergie des Actions Républicaines (ALSAR), consacrant un travail de terrain engagé depuis 2014 et ouvrant une nouvelle phase de structuration nationale."],
                ],
                'image' => $this->img($slug, 'histoire', 'image', '/images/slider-img-1.webp', "ALSAR — illustration de l'histoire du parti"),
            ],
            'organisation' => [
                'kicker' => 'Organisation',
                'title' => 'Une structuration en cours',
                'facts' => [
                    ['label' => 'Présidence du parti', 'value' => 'Mamadou Racine Sy'],
                    ['label' => 'Coordination locale identifiée', 'value' => 'ALSAR Sagatta — Ndeye Sarr Diop'],
                ],
                'note' => "Les mouvements des jeunes et des femmes ainsi que les coordinations régionales/départementales sont en cours de structuration officielle. Cette page sera complétée au fur et à mesure de leur mise en place.",
            ],
            'statuts' => [
                'kicker' => 'Statuts & Transparence',
                'title' => "Statuts officiels et rapports d'activité",
                'coming_soon_text' => "Les statuts officiels du parti et les rapports d'activité seront publiés ici dès leur validation. C'est un engagement de transparence, pas un simple espace à remplir.",
            ],
        ];
    }

    private function lePresident(): array
    {
        $slug = 'le-president';

        return [
            'header' => [
                'kicker' => 'Le Président',
                'title' => 'Mamadou Racine Sy',
                'intro' => "Entrepreneur, maire de Podor depuis janvier 2022, fondateur en 2014 du mouvement ALPAR à l'origine d'ALSAR — un parcours entre réussite entrepreneuriale et engagement public.",
                'image' => $this->img($slug, 'header', 'image', '/images/slider-img-4.webp', 'Mamadou Racine Sy sur le terrain'),
            ],
            'portrait' => [
                'kicker' => 'Portrait',
                'title' => "Un homme d'action, entre entreprise et service public.",
                'body' => "Originaire de Soueïma, dans la commune de Podor (région du Fouta), Mamadou Racine Sy s'est imposé au fil des décennies comme l'un des principaux acteurs du tourisme, de l'hôtellerie et du patronat au Sénégal et en Afrique de l'Ouest, avant de mettre cette expérience au service des populations par l'engagement public. Il siège aujourd'hui dans plusieurs conseils d'administration de grandes institutions, dont l'IPRES, la Caisse de Sécurité Sociale, Sénégal Hôtels et Africa Global Logistics (AGL), et porte à la tête d'ALSAR une troisième voie pragmatique pour le Sénégal.",
                'facts' => [
                    ['mode' => 'count_up_year', 'value' => '2022', 'label' => 'ans à la tête de la mairie de Podor'],
                    ['mode' => 'static', 'value' => '1', 'label' => 'mandat municipal (depuis 2022)'],
                    ['mode' => 'static', 'value' => '1er', 'label' => 'Vice-Président du Conseil National du Patronat'],
                ],
                'image' => $this->img($slug, 'portrait', 'image', '/images/Photo-du-président-racine-sy-1.jpeg', "Mamadou Racine Sy, Président d'ALSAR"),
            ],
            'realisations' => [
                'title' => 'Réalisations à Podor',
                'body' => "Modernisation du marché central, projets d'assainissement, d'éclairage public, de voirie et d'aménagement urbain, actions sociales récurrentes (distribution de bœufs pendant le Ramadan, soutien aux étudiants) et revendication portée pour l'érection du département de Podor en région à part entière.",
            ],
            'parcours' => [
                'title' => 'Parcours',
                'career' => [
                    ['date' => 'Années 1980', 'text' => "Débuts dans le secteur privé sénégalais et l'hôtellerie, au sein du groupe hôtelier familial."],
                    ['date' => 'Années 1990', 'text' => 'Développement des activités hôtelières du Groupe Sy, qui devient un acteur majeur du tourisme sénégalais.'],
                    ['date' => 'Années 2000', 'text' => "Direction du King Fahd Palace : renforce le groupe hôtelier et œuvre au rayonnement international de la destination Sénégal, à la création d'emplois et à l'investissement touristique."],
                    ['date' => 'Années 2000-2020', 'text' => "Accède aux principales responsabilités patronales nationales et régionales : Président de la Fédération des Organisations Patronales de l'Industrie Touristique du Sénégal (FOPITS), Président du Conseil National du Tourisme, Président de la COPITOUR/CEDEAO, Premier Vice-Président du Conseil National du Patronat (CNP). Il siège également dans plusieurs conseils d'administration, dont l'IPRES, la Caisse de Sécurité Sociale, Sénégal Hôtels et Africa Global Logistics (AGL)."],
                    ['date' => '2014', 'text' => "Convaincu que le développement doit partir des territoires, crée ALPAR (And Liggey Podor ak Racine), mouvement citoyen destiné à accompagner les initiatives de développement dans le département de Podor."],
                    ['date' => '2019', 'text' => "Face à l'adhésion grandissante des populations, ALPAR évolue pour devenir ALSAR (And Liggey Sénégal ak Racine), mouvement national porté par le travail, la solidarité, l'investissement productif et la bonne gouvernance."],
                    ['date' => 'Janvier 2022', 'text' => "Élu maire de la commune de Podor — conduit depuis lors plusieurs projets structurants : assainissement, éclairage public, modernisation du marché central, voirie, aménagement urbain et actions sociales en faveur des jeunes, des femmes et des personnes vulnérables."],
                    ['date' => '2024', 'text' => "Le mouvement ALSAR participe pour la première fois à une élection nationale, en présentant une liste indépendante aux élections législatives."],
                    ['date' => '2026', 'text' => "Après plus d'une décennie d'implantation progressive commencée avec ALPAR, ALSAR obtient sa reconnaissance officielle comme parti politique sous l'appellation Alliance pour la Synergie des Actions Républicaines (ALSAR) — ouvrant une nouvelle phase de structuration nationale."],
                ],
                'image' => $this->img($slug, 'parcours', 'image', '/images/Photo-président-racine-sy-2.jpeg', 'ALSAR — illustration du parcours du Président'),
            ],
            'discours' => [
                'kicker' => 'Discours & Revue de presse',
                'title' => 'Sa parole, relayée par la presse sénégalaise',
                'press_outlets' => [
                    ['text' => 'Dakaractu'],
                    ['text' => 'La Vie Sénégalaise'],
                    ['text' => 'SansLimiteSn'],
                ],
                'coming_soon_text' => "Aucun discours filmé n'est pour l'instant documenté publiquement. Cette section sera alimentée au fur et à mesure des interventions du Président.",
            ],
        ];
    }

    private function programme(): array
    {
        $slug = 'programme';

        return [
            'header' => [
                'kicker' => 'Programme',
                'title' => 'Six axes pour transformer le Sénégal',
                'intro' => 'Un pragmatisme orienté résultats, structuré autour de six priorités : économie, agriculture, infrastructures, foncier, égalité et gouvernance.',
                'image' => $this->img($slug, 'header', 'image', '/images/slider-img-3.webp', 'ALSAR sur le terrain'),
            ],
            'intro' => [
                'text' => "Ce programme est aujourd'hui énoncé sous forme de grands principes. Une version chiffrée, avec échéances et indicateurs de suivi, est en préparation — téléchargement du programme complet (PDF) à venir.",
            ],
            'axes' => [
                'axes' => [
                    ['id' => 'emploi', 'icon' => 'Briefcase', 'title' => "Pouvoir d'achat et emploi", 'text' => "Création d'emplois, soutien à l'entrepreneuriat et modernisation des secteurs clés pour une croissance inclusive. Allégement fiscal sur les produits de première nécessité et révision de l'impôt sur le revenu."],
                    ['id' => 'alimentaire', 'icon' => 'Wheat', 'title' => 'Souveraineté alimentaire', 'text' => 'Modernisation du secteur agropastoral, sécurité alimentaire, protection des droits des agriculteurs et éleveurs.'],
                    ['id' => 'desenclavement', 'icon' => 'Route', 'title' => 'Désenclavement et développement rural', 'text' => "Programme d'infrastructures rurales pour connecter le pays aux opportunités de développement."],
                    ['id' => 'foncier', 'icon' => 'LandPlot', 'title' => 'Sécurisation foncière', 'text' => 'Garantir un accès équitable et sécurisé au foncier, question sensible pour de nombreuses collectivités locales.'],
                    ['id' => 'femmes', 'icon' => 'HeartHandshake', 'title' => 'Droits des femmes et politique familiale', 'text' => 'Renforcement des droits des femmes et construction d’une société plus juste et inclusive.'],
                    ['id' => 'gouvernance', 'icon' => 'ShieldCheck', 'title' => 'Transparence et bonne gouvernance', 'text' => 'Mécanismes de contrôle et de transparence dans la gestion des ressources publiques.'],
                ],
            ],
        ];
    }

    private function regions(): array
    {
        $slug = 'regions';

        return [
            'header' => [
                'kicker' => 'Régions',
                'title' => 'Une présence nationale, région par région',
                'intro' => 'ALSAR est présent dans les 14 régions du pays — cliquez sur une région de la carte interactive pour découvrir les informations disponibles sur sa coordination locale.',
                'image' => $this->img($slug, 'header', 'image', '/images/slider-img-4.webp', 'ALSAR sur le terrain'),
            ],
            'map' => [
                'caption' => 'Cliquez sur une région (ou naviguez au clavier) pour afficher les informations disponibles.',
            ],
            'region_info' => [
                'regions' => [
                    ['region_name' => 'Dakar', 'section' => 'Siège national', 'contact' => '', 'note' => 'Siège officiel du parti — Cité Keur Gorgui, immeuble Hermès 2, Dakar.'],
                    ['region_name' => 'Saint-Louis', 'section' => 'ALSAR Sagatta', 'contact' => 'Ndeye Sarr Diop', 'note' => 'Coordination locale à Podor, berceau historique du mouvement (ALPAR, 2014), identifiée publiquement.'],
                ],
            ],
            'default_note' => [
                'text' => "ALSAR est présent dans cette région. Le nom et les contacts de sa coordination locale ne sont pas encore publiés — cette page sera mise à jour au fur et à mesure de leur communication officielle.",
            ],
            'sections_locales' => [
                'kicker' => 'Sections locales',
                'title' => 'Présent dans les 14 régions, coordination détaillée au fur et à mesure',
            ],
        ];
    }

    private function mediatheque(): array
    {
        $slug = 'mediatheque';

        return [
            'header' => [
                'kicker' => 'Médiathèque',
                'title' => 'Photos, vidéos et ressources presse',
                'intro' => 'La vie du parti en images — galerie photo, vidéo et espace presse téléchargeable.',
                'image' => $this->img($slug, 'header', 'image', '/images/slider-img-5.webp', 'ALSAR sur le terrain'),
            ],
            'gallery' => [
                'kicker' => 'Galerie',
                'title' => 'Photos',
                'intro' => "Cliquez sur une photo pour l'agrandir.",
                'photos' => [
                    ['image' => $this->img($slug, 'gallery', 'photos/0', '/images/slider-img-1.webp', 'ALSAR sur le terrain à Podor (photo 1)'), 'caption' => ''],
                    ['image' => $this->img($slug, 'gallery', 'photos/1', '/images/slider-img-2.webp', 'ALSAR sur le terrain à Podor (photo 2)'), 'caption' => ''],
                    ['image' => $this->img($slug, 'gallery', 'photos/2', '/images/slider-img-3.webp', 'ALSAR sur le terrain à Podor (photo 3)'), 'caption' => ''],
                    ['image' => $this->img($slug, 'gallery', 'photos/3', '/images/slider-img-4.webp', 'ALSAR sur le terrain à Podor (photo 4)'), 'caption' => ''],
                    ['image' => $this->img($slug, 'gallery', 'photos/4', '/images/slider-img-5.webp', 'ALSAR sur le terrain à Podor (photo 5)'), 'caption' => ''],
                    ['image' => $this->img($slug, 'gallery', 'photos/5', '/images/imgi_67_.webp', "ALSAR — photo d'archive"), 'caption' => ''],
                    ['image' => $this->img($slug, 'gallery', 'photos/6', '/images/photo-de-racine-sy.webp', "Mamadou Racine Sy, Président d'ALSAR"), 'caption' => "Mamadou Racine Sy, Président d'ALSAR"],
                ],
            ],
            'video' => [
                'kicker' => 'Vidéo',
                'title' => 'ALSAR en mouvement',
                'body' => "Un aperçu du terrain et de la mobilisation militante d'ALSAR.",
            ],
            'podcasts' => [
                'kicker' => 'Podcasts & interviews',
                'title' => 'Bientôt disponible',
                'coming_soon_text' => "Aucune interview ou podcast n'est aujourd'hui documenté publiquement. Cette section sera alimentée au fur et à mesure des prises de parole du parti.",
            ],
            'presse' => [
                'kicker' => 'Espace presse',
                'title' => 'Kit média',
                'body' => "Le dossier de presse complet (photos HD, biographie courte, communiqués) est en préparation. Le logo du parti est d'ores et déjà disponible au téléchargement.",
            ],
        ];
    }

    private function siteSettings(): array
    {
        $slug = 'site-settings';

        return [
            'header' => [
                'brand_text' => 'ALSAR',
                'logo' => $this->img($slug, 'header', 'logo', '/images/logo-alsar.png'),
                'nav_items' => [
                    ['label' => 'Le Parti'],
                    ['label' => 'Le Président'],
                    ['label' => 'Programme'],
                    ['label' => 'Actualités'],
                    ['label' => 'Médiathèque'],
                    ['label' => 'Régions'],
                ],
                'cta_label' => 'Adhérer',
            ],
            'footer' => [
                'brand_text' => 'ALSAR',
                'logo' => $this->img($slug, 'footer', 'logo', '/images/logo-alsar.png'),
                'tagline' => 'Alliance pour la Synergie des Actions Républicaines — Cité Keur Gorgui, Dakar.',
                'contact_email' => 'contact@alsar.sn',
                'contact_address' => 'Cité Keur Gorgui, immeuble Hermès 2, Dakar',
                'facebook_url' => '',
                'copyright_text' => 'ALSAR. Tous droits réservés.',
            ],
        ];
    }

    /**
     * Copie un asset public existant (public/{$sourcePublicPath}) vers le disque `public`
     * managé, sous un chemin namespacé par page/section/champ — même convention que
     * UpdatePageContent::resolveImage(), pour que le nettoyage des orphelins fonctionne
     * correctement dès la première modification faite par un admin. Silencieux (path=null)
     * si le fichier source n'existe plus, plutôt que de faire échouer tout le seeder.
     */
    private function img(string $slug, string $section, string $fieldPath, string $sourcePublicPath, string $alt = ''): array
    {
        $source = public_path(ltrim($sourcePublicPath, '/'));

        if (! is_file($source)) {
            return ['path' => null, 'alt' => $alt];
        }

        $storagePath = "pages/{$slug}/{$section}/{$fieldPath}/".basename($sourcePublicPath);
        Storage::disk('public')->put($storagePath, file_get_contents($source));

        return ['path' => $storagePath, 'alt' => $alt];
    }
}
