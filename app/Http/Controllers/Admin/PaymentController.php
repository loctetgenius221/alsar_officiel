<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Payments\Actions\ConfirmMembershipPayment;
use App\Domain\Payments\Models\MembershipPayment;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PaymentController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $payments = MembershipPayment::with('member')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('tier'), fn ($query) => $query->where('tier', $request->string('tier')))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (MembershipPayment $payment) => [
                'id' => $payment->id,
                'memberName' => $payment->member?->fullName() ?? '—',
                'membershipCode' => $payment->member?->membership_code,
                'tier' => $payment->tier,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'gateway' => $payment->gateway,
                'gatewayReference' => $payment->gateway_reference,
                'status' => $payment->status,
                'paidAt' => $payment->paid_at?->translatedFormat('d M Y, H:i'),
                'createdAt' => $payment->created_at->translatedFormat('d M Y, H:i'),
            ]);

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only('status', 'tier'),
            'tiers' => config('adhesion.tiers'),
        ]);
    }

    public function confirmManually(MembershipPayment $payment, ConfirmMembershipPayment $confirmMembershipPayment): RedirectResponse
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Ce paiement a déjà été traité.');
        }

        $confirmMembershipPayment->handle($payment);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($payment)
            ->log('Paiement confirmé manuellement (hors-ligne)');

        return back()->with('success', 'Paiement confirmé, adhérent activé.');
    }
}
