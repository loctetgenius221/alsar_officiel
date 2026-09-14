<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Models\MembershipPayment;

/**
 * Abstraction commune à tous les prestataires de paiement (CLAUDE.md §2.2). Aujourd'hui,
 * seule `Gateways\SimulationGateway` existe ; brancher un vrai prestataire (PayTech,
 * CinetPay, Wave, Stripe) consiste à ajouter une classe qui implémente cette interface,
 * sans toucher au reste du parcours d'adhésion.
 */
interface PaymentGatewayInterface
{
    /**
     * Initie le paiement et renvoie l'URL vers laquelle rediriger l'adhérent
     * (le checkout du prestataire pour un vrai gateway).
     */
    public function charge(MembershipPayment $payment): string;

    /**
     * Traite un webhook entrant du prestataire (signature à vérifier par l'implémentation
     * concrète). Ne fait jamais confiance à une simple redirection navigateur — CLAUDE.md
     * §2.2 : "le webhook fait foi".
     */
    public function handleWebhook(array $payload, array $headers): void;
}
