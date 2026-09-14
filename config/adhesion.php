<?php

return [
    /*
     * Montants indicatifs en FCFA (XOF) — À VALIDER AVEC ALSAR avant mise en production.
     * Source unique et autoritaire : le montant réellement débité est TOUJOURS recalculé
     * depuis ce fichier côté serveur, jamais depuis une valeur envoyée par le client
     * (cf. plan Phase 2, sécurité anti-tampering).
     */
    'tiers' => [
        'standard' => [
            'label' => 'Standard',
            'amount' => 5000,
        ],
        'soutien' => [
            'label' => 'Soutien',
            'amount' => 15000,
        ],
        'bienfaiteur' => [
            'label' => 'Bienfaiteur',
            'amount' => 50000,
        ],
    ],

    'currency' => 'XOF',

    /*
     * Les 14 vraies régions du Sénégal (doit rester synchronisé avec
     * resources/js/lib/senegal-regions.ts — pas de référentiel département/commune
     * complet pour ce lot, cf. plan Phase 2).
     */
    'regions' => [
        'Dakar', 'Thiès', 'Diourbel', 'Fatick', 'Kaolack', 'Kaffrine',
        'Louga', 'Saint-Louis', 'Matam', 'Tambacounda', 'Kédougou',
        'Kolda', 'Sédhiou', 'Ziguinchor',
    ],
];
