<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Actions\ConfirmMembershipPayment;
use App\Domain\Payments\Models\MembershipPayment;
use App\Domain\Payments\PaymentGatewayInterface;
use Illuminate\Support\Str;

/**
 * Passerelle temporaire en attendant l'intégration d'un vrai prestataire (PayTech, CinetPay,
 * Wave...). Confirme toujours le paiement avec succès — pas de simulation d'échec aléatoire,
 * ce n'est pas son rôle (cf. plan Phase 2 : "paiement traité en dernier").
 */
class SimulationGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly ConfirmMembershipPayment $confirmMembershipPayment,
    ) {}

    public function charge(MembershipPayment $payment): string
    {
        $payment->forceFill([
            'gateway' => 'simulation',
            'gateway_reference' => 'SIM-'.strtoupper(Str::random(12)),
        ])->save();

        // Un vrai prestataire redirigerait vers son propre checkout, puis appellerait notre
        // webhook de façon asynchrone. Ici on "reçoit" ce webhook immédiatement.
        $this->confirmMembershipPayment->handle($payment);

        return route('adhesion.confirmation', $payment->member->membership_code);
    }

    public function handleWebhook(array $payload, array $headers): void
    {
        $reference = $payload['gateway_reference'] ?? null;

        if (! $reference) {
            return;
        }

        $payment = MembershipPayment::where('gateway_reference', $reference)->first();

        if ($payment) {
            $this->confirmMembershipPayment->handle($payment);
        }
    }
}
