<?php

/*
|--------------------------------------------------------------------------
| Messages de validation — français
|--------------------------------------------------------------------------
|
| `APP_LOCALE=fr` mais aucun fichier de langue n'existait : Laravel servait donc ses
| messages anglais par défaut ("The title field is required.") à des utilisateurs
| francophones et non techniques. Ce fichier doit rester COMPLET : dès qu'un
| `lang/fr/validation.php` existe, une clé manquante n'est plus rattrapée par l'anglais,
| elle s'affiche telle quelle à l'écran ("validation.uuid").
|
| Ton retenu : on s'adresse à un secrétariat / community manager, pas à un développeur.
| Pas de jargon ("chaîne de caractères", "entier", "booléen"), et on dit ce qu'il faut
| FAIRE, pas seulement ce qui est faux.
|
*/

return [

    'accepted' => 'Vous devez cocher « :attribute » pour continuer.',
    'accepted_if' => 'Vous devez cocher « :attribute » lorsque :other vaut :value.',
    'active_url' => 'Le champ :attribute doit contenir une adresse web valide.',
    'after' => 'La date :attribute doit être postérieure au :date.',
    'after_or_equal' => 'La date :attribute doit être le :date ou une date postérieure.',
    'alpha' => 'Le champ :attribute ne peut contenir que des lettres.',
    'alpha_dash' => 'Le champ :attribute ne peut contenir que des lettres, des chiffres et des tirets.',
    'alpha_num' => 'Le champ :attribute ne peut contenir que des lettres et des chiffres.',
    'any_of' => "La valeur du champ :attribute n'est pas valide.",
    'array' => "Le champ :attribute n'a pas été reçu dans un format exploitable.",
    'ascii' => 'Le champ :attribute ne peut contenir ni accents ni caractères spéciaux.',
    'base64' => "Le champ :attribute n'a pas été reçu dans un format exploitable.",
    'before' => 'La date :attribute doit être antérieure au :date.',
    'before_or_equal' => 'La date :attribute doit être le :date ou une date antérieure.',
    'between' => [
        'array' => 'Le champ :attribute doit contenir entre :min et :max éléments.',
        'file' => 'Le fichier :attribute doit peser entre :min et :max Ko.',
        'numeric' => 'Le champ :attribute doit être compris entre :min et :max.',
        'string' => 'Le champ :attribute doit contenir entre :min et :max caractères.',
    ],
    'boolean' => 'Le champ :attribute ne peut valoir que oui ou non.',
    'can' => 'Le champ :attribute contient une valeur non autorisée.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'contains' => 'Il manque une valeur obligatoire dans le champ :attribute.',
    'current_password' => 'Le mot de passe actuel est incorrect.',
    'date' => "La date :attribute n'est pas une date valide.",
    'date_equals' => 'La date :attribute doit être le :date.',
    'date_format' => "La date :attribute n'est pas au bon format.",
    'decimal' => 'Le champ :attribute doit comporter :decimal chiffres après la virgule.',
    'declined' => 'Le champ :attribute doit être refusé.',
    'declined_if' => 'Le champ :attribute doit être refusé lorsque :other vaut :value.',
    'different' => 'Les champs :attribute et :other doivent être différents.',
    'digits' => 'Le champ :attribute doit contenir :digits chiffres.',
    'digits_between' => 'Le champ :attribute doit contenir entre :min et :max chiffres.',
    'dimensions' => "Les dimensions de l'image :attribute ne sont pas acceptées.",
    'distinct' => 'Le champ :attribute contient une valeur en double.',
    'doesnt_contain' => 'Le champ :attribute ne doit contenir aucune des valeurs suivantes : :values.',
    'doesnt_end_with' => 'Le champ :attribute ne doit pas se terminer par : :values.',
    'doesnt_start_with' => 'Le champ :attribute ne doit pas commencer par : :values.',
    'email' => 'Le champ :attribute doit contenir une adresse email valide (exemple : nom@domaine.sn).',
    'encoding' => 'Le champ :attribute doit être encodé en :encoding.',
    'ends_with' => "Le champ :attribute doit se terminer par l'une des valeurs suivantes : :values.",
    'enum' => "La valeur sélectionnée pour :attribute n'est pas valide.",
    'exists' => "La valeur sélectionnée pour :attribute n'existe pas ou a été supprimée entre-temps.",
    'extensions' => "Le fichier :attribute doit avoir l'une de ces extensions : :values.",
    'file' => 'Le champ :attribute doit contenir un fichier.',
    'filled' => 'Le champ :attribute ne peut pas être vide.',
    'gt' => [
        'array' => 'Le champ :attribute doit contenir plus de :value éléments.',
        'file' => 'Le fichier :attribute doit peser plus de :value Ko.',
        'numeric' => 'Le champ :attribute doit être supérieur à :value.',
        'string' => 'Le champ :attribute doit contenir plus de :value caractères.',
    ],
    'gte' => [
        'array' => 'Le champ :attribute doit contenir au moins :value éléments.',
        'file' => 'Le fichier :attribute doit peser au moins :value Ko.',
        'numeric' => 'Le champ :attribute doit être supérieur ou égal à :value.',
        'string' => 'Le champ :attribute doit contenir au moins :value caractères.',
    ],
    'hex_color' => 'Le champ :attribute doit contenir une couleur valide.',
    'image' => 'Le fichier :attribute doit être une image (JPG, PNG ou WebP).',
    'in' => "La valeur sélectionnée pour :attribute n'est pas valide.",
    'in_array' => 'Le champ :attribute doit figurer dans :other.',
    'in_array_keys' => "Le champ :attribute doit contenir au moins l'une des clés suivantes : :values.",
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'ip' => 'Le champ :attribute doit contenir une adresse IP valide.',
    'ipv4' => 'Le champ :attribute doit contenir une adresse IPv4 valide.',
    'ipv6' => 'Le champ :attribute doit contenir une adresse IPv6 valide.',
    'json' => "Le champ :attribute n'a pas été reçu dans un format exploitable.",
    'list' => "Le champ :attribute n'a pas été reçu dans un format exploitable.",
    'lowercase' => 'Le champ :attribute doit être écrit en minuscules.',
    'lt' => [
        'array' => 'Le champ :attribute doit contenir moins de :value éléments.',
        'file' => 'Le fichier :attribute doit peser moins de :value Ko.',
        'numeric' => 'Le champ :attribute doit être inférieur à :value.',
        'string' => 'Le champ :attribute doit contenir moins de :value caractères.',
    ],
    'lte' => [
        'array' => 'Le champ :attribute ne doit pas contenir plus de :value éléments.',
        'file' => 'Le fichier :attribute ne doit pas peser plus de :value Ko.',
        'numeric' => 'Le champ :attribute doit être inférieur ou égal à :value.',
        'string' => 'Le champ :attribute ne doit pas contenir plus de :value caractères.',
    ],
    'mac_address' => 'Le champ :attribute doit contenir une adresse MAC valide.',
    'max' => [
        'array' => 'Le champ :attribute ne peut pas contenir plus de :max éléments.',
        'file' => 'Le fichier :attribute est trop volumineux (:max Ko maximum).',
        'numeric' => 'Le champ :attribute ne peut pas dépasser :max.',
        'string' => 'Le champ :attribute est trop long (:max caractères maximum).',
    ],
    'max_digits' => 'Le champ :attribute ne peut pas contenir plus de :max chiffres.',
    'mimes' => 'Le fichier :attribute doit être au format : :values.',
    'mimetypes' => 'Le fichier :attribute doit être au format : :values.',
    'min' => [
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'file' => 'Le fichier :attribute doit peser au moins :min Ko.',
        'numeric' => 'Le champ :attribute doit être au moins égal à :min.',
        'string' => 'Le champ :attribute est trop court (:min caractères minimum).',
    ],
    'min_digits' => 'Le champ :attribute doit contenir au moins :min chiffres.',
    'missing' => 'Le champ :attribute ne doit pas être renseigné.',
    'missing_if' => 'Le champ :attribute ne doit pas être renseigné lorsque :other vaut :value.',
    'missing_unless' => 'Le champ :attribute ne doit pas être renseigné sauf si :other vaut :value.',
    'missing_with' => 'Le champ :attribute ne doit pas être renseigné lorsque :values est présent.',
    'missing_with_all' => 'Le champ :attribute ne doit pas être renseigné lorsque :values sont présents.',
    'multiple_of' => 'Le champ :attribute doit être un multiple de :value.',
    'not_in' => "La valeur sélectionnée pour :attribute n'est pas valide.",
    'not_regex' => "Le format du champ :attribute n'est pas valide.",
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'password' => [
        'letters' => 'Le mot de passe doit contenir au moins une lettre.',
        'mixed' => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
        'symbols' => 'Le mot de passe doit contenir au moins un caractère spécial.',
        'uncompromised' => 'Ce mot de passe est apparu dans une fuite de données publique. Choisissez-en un autre.',
    ],
    'present' => 'Le champ :attribute doit être envoyé.',
    'present_if' => 'Le champ :attribute doit être envoyé lorsque :other vaut :value.',
    'present_unless' => 'Le champ :attribute doit être envoyé sauf si :other vaut :value.',
    'present_with' => 'Le champ :attribute doit être envoyé lorsque :values est présent.',
    'present_with_all' => 'Le champ :attribute doit être envoyé lorsque :values sont présents.',
    'prohibited' => 'Le champ :attribute ne peut pas être renseigné.',
    'prohibited_if' => 'Le champ :attribute ne peut pas être renseigné lorsque :other vaut :value.',
    'prohibited_if_accepted' => 'Le champ :attribute ne peut pas être renseigné lorsque :other est accepté.',
    'prohibited_if_declined' => 'Le champ :attribute ne peut pas être renseigné lorsque :other est refusé.',
    'prohibited_unless' => 'Le champ :attribute ne peut pas être renseigné sauf si :other fait partie de :values.',
    'prohibits' => "Le champ :attribute empêche :other d'être renseigné.",
    'regex' => "Le format du champ :attribute n'est pas valide.",
    'required' => 'Le champ :attribute est obligatoire.',
    'required_array_keys' => 'Le champ :attribute doit contenir les entrées suivantes : :values.',
    'required_if' => 'Le champ :attribute est obligatoire lorsque :other vaut :value.',
    'required_if_accepted' => 'Le champ :attribute est obligatoire lorsque :other est accepté.',
    'required_if_declined' => 'Le champ :attribute est obligatoire lorsque :other est refusé.',
    'required_unless' => 'Le champ :attribute est obligatoire sauf si :other fait partie de :values.',
    'required_with' => 'Le champ :attribute est obligatoire lorsque :values est renseigné.',
    'required_with_all' => 'Le champ :attribute est obligatoire lorsque :values sont renseignés.',
    'required_without' => "Le champ :attribute est obligatoire lorsque :values n'est pas renseigné.",
    'required_without_all' => "Le champ :attribute est obligatoire lorsqu'aucun de ces champs n'est renseigné : :values.",
    'same' => 'Les champs :attribute et :other doivent être identiques.',
    'size' => [
        'array' => 'Le champ :attribute doit contenir :size éléments.',
        'file' => 'Le fichier :attribute doit peser :size Ko.',
        'numeric' => 'Le champ :attribute doit valoir :size.',
        'string' => 'Le champ :attribute doit contenir :size caractères.',
    ],
    'starts_with' => "Le champ :attribute doit commencer par l'une des valeurs suivantes : :values.",
    'string' => 'Le champ :attribute doit être du texte.',
    'timezone' => 'Le champ :attribute doit contenir un fuseau horaire valide.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'uploaded' => "Le fichier :attribute n'a pas pu être envoyé. Il est peut-être trop volumineux, ou la connexion a été interrompue.",
    'uppercase' => 'Le champ :attribute doit être écrit en majuscules.',
    'url' => 'Le champ :attribute doit contenir une adresse web valide (commençant par https://).',
    'ulid' => "Le champ :attribute n'a pas été reçu dans un format exploitable.",
    'uuid' => "Le champ :attribute n'a pas été reçu dans un format exploitable.",

    /*
    |--------------------------------------------------------------------------
    | Messages sur mesure (attribut.règle)
    |--------------------------------------------------------------------------
    |
    | Priorité sur les messages génériques ci-dessus quand on peut être plus concret :
    | dire « 20 Mo » plutôt que « 20480 Ko », expliquer à quoi sert la catégorie, etc.
    |
    */

    'custom' => [
        'cover_image' => [
            'max' => "L'image de couverture est trop lourde (20 Mo maximum). Réduisez-la avant de réessayer.",
            'image' => "Le fichier choisi comme image de couverture n'est pas une image.",
            'mimes' => "L'image de couverture doit être au format JPG, PNG, WebP, GIF ou BMP.",
        ],
        'photo' => [
            'max' => 'La photo est trop lourde (4 Mo maximum). Réduisez-la avant de réessayer.',
            'image' => "Le fichier choisi comme photo n'est pas une image.",
            'mimes' => 'La photo doit être au format JPG, PNG ou WebP.',
        ],
        'category_id' => [
            'required' => "Choisissez une catégorie : elle détermine l'endroit où l'article apparaît sur le site.",
            'exists' => "Cette catégorie n'existe plus. Rechargez la page et choisissez-en une autre.",
        ],
        'published_at' => [
            'date' => 'La date de publication est illisible. Choisissez-la dans le calendrier.',
        ],
        'email' => [
            'unique' => 'Cette adresse email est déjà associée à une adhésion.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Noms lisibles des champs
    |--------------------------------------------------------------------------
    |
    | Sans ces libellés, un message reprendrait le nom technique de la colonne
    | (« Le champ first_name est obligatoire »). Couvre l'adhésion publique, les
    | articles et la connexion admin.
    |
    */

    'attributes' => [
        // Adhésion publique
        'first_name' => 'prénom',
        'last_name' => 'nom',
        'birth_date' => 'date de naissance',
        'gender' => 'sexe',
        'phone' => 'téléphone',
        'email' => 'adresse email',
        'region' => 'région',
        'commune' => 'commune',
        'address' => 'adresse',
        'profession' => 'profession',
        'photo' => 'photo',
        'id_document_number' => "numéro de pièce d'identité",
        'section' => 'section',
        'referral_source' => 'origine de la candidature',
        'consent' => 'les statuts et la politique de confidentialité',
        'tier' => 'palier de cotisation',

        // Articles / contenu
        'title' => 'titre',
        'slug' => 'adresse de la page',
        'excerpt' => 'extrait',
        'body' => 'contenu',
        'cover_image' => 'image de couverture',
        'category_id' => 'catégorie',
        'published_at' => 'date de publication',
        'seo_title' => 'titre SEO',
        'seo_description' => 'description SEO',
        'note' => 'note interne',

        // Connexion admin
        'password' => 'mot de passe',
        'remember' => 'se souvenir de moi',

        // Comptes du back-office
        'name' => 'nom complet',
        'role' => 'rôle',
        'current_password' => 'mot de passe actuel',
        'password_confirmation' => 'confirmation du mot de passe',
    ],

];
