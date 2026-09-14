<?php

namespace App\Http\Controllers\Site;

use App\Domain\Members\Actions\BuildMembershipQrCode;
use App\Domain\Members\Actions\RegisterMember;
use App\Domain\Members\Models\Member;
use App\Domain\Payments\Gateways\SimulationGateway;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdhesionController extends Controller
{
    public function create(): InertiaResponse
    {
        // `tiers` n'est plus transmis : cotisation désactivée pour le moment (wizard réduit à
        // 2 étapes, cf. Pages/Site/Adhesion.vue) — `config('adhesion.tiers')` reste néanmoins
        // la source de vérité côté serveur si la cotisation est un jour réactivée.
        return Inertia::render('Site/Adhesion', [
            'regions' => config('adhesion.regions'),
        ]);
    }

    public function store(StoreMemberRequest $request, RegisterMember $registerMember, SimulationGateway $gateway): RedirectResponse
    {
        $data = $request->validated();
        $tier = $data['tier'] ?? null;
        $amount = $tier ? config("adhesion.tiers.{$tier}.amount") : null;

        $member = $registerMember->handle($data, $tier, $amount);

        $payment = $member->payments->first();

        if ($payment) {
            $redirectUrl = $gateway->charge($payment);

            return redirect($redirectUrl);
        }

        return redirect()->route('adhesion.confirmation', $member->membership_code);
    }

    public function confirmation(string $membershipCode, BuildMembershipQrCode $buildQrCode): InertiaResponse
    {
        $member = Member::where('membership_code', $membershipCode)->first();

        if (! $member) {
            return Inertia::render('Site/AdhesionConfirmation', ['member' => null]);
        }

        $qrCode = $member->status === 'active' ? $buildQrCode->handle($member, 220) : null;

        return Inertia::render('Site/AdhesionConfirmation', [
            'member' => [
                'membershipCode' => $member->membership_code,
                'fullName' => $member->fullName(),
                'status' => $member->status,
                'section' => $member->section,
                'joinedAt' => $member->joined_at?->translatedFormat('d F Y'),
            ],
            'qrCode' => $qrCode,
        ]);
    }

    public function card(string $membershipCode, BuildMembershipQrCode $buildQrCode): Response
    {
        $member = Member::where('membership_code', $membershipCode)->where('status', 'active')->firstOrFail();

        return Pdf::loadView('pdf.membership-card', [
            'member' => $member,
            'qrCode' => $buildQrCode->handle($member),
            'photo' => $member->photoDataUri(),
        ])->download("carte-adhesion-{$member->membership_code}.pdf");
    }

    /**
     * Vérification en direct de la disponibilité d'un email pendant la saisie (étape
     * Identité) — évite qu'un adhérent découvre "email déjà utilisé" seulement après avoir
     * rempli les 3 étapes. Ne révèle rien de plus que l'erreur `email.unique` déjà présente
     * à la soumission finale, juste plus tôt. Throttlée séparément pour éviter d'en faire un
     * outil de scan d'emails à grande échelle.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        return response()->json([
            'available' => ! Member::where('email', $data['email'])->exists(),
        ]);
    }

    public function verify(string $membershipCode): InertiaResponse
    {
        $member = Member::where('membership_code', $membershipCode)
            ->whereIn('status', ['active', 'pending'])
            ->first();

        return Inertia::render('Site/Verifier', [
            'result' => $member ? [
                'valid' => $member->status === 'active',
                'fullName' => $member->fullName(),
                'status' => $member->status,
                'section' => $member->section,
                'joinedAt' => $member->joined_at?->translatedFormat('d F Y'),
            ] : null,
        ]);
    }

    public function webhook(SimulationGateway $gateway): Response
    {
        $gateway->handleWebhook(request()->all(), request()->headers->all());

        return response()->noContent();
    }
}
