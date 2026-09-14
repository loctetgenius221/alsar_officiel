<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Members\Actions\ResendMembershipCard;
use App\Domain\Members\Actions\ToggleMemberStatus;
use App\Domain\Members\Models\Member;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $members = $this->filteredQuery($request)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Member $member) => [
                'id' => $member->id,
                'membershipCode' => $member->membership_code,
                'fullName' => $member->fullName(),
                'email' => $member->email,
                'phone' => $member->phone,
                'region' => $member->region,
                'section' => $member->section,
                'status' => $member->status,
                'joinedAt' => $member->joined_at?->translatedFormat('d M Y'),
            ]);

        return Inertia::render('Admin/Members/Index', [
            'members' => $members,
            'filters' => $request->only('search', 'region', 'status', 'section'),
            'regions' => config('adhesion.regions'),
        ]);
    }

    public function show(Member $member): InertiaResponse
    {
        $member->load(['payments' => fn ($query) => $query->latest('created_at')]);

        return Inertia::render('Admin/Members/Show', [
            'member' => [
                'id' => $member->id,
                'membershipCode' => $member->membership_code,
                'fullName' => $member->fullName(),
                'email' => $member->email,
                'phone' => $member->phone,
                'birthDate' => $member->birth_date?->translatedFormat('d F Y'),
                'gender' => $member->gender,
                'region' => $member->region,
                'commune' => $member->commune,
                'address' => $member->address,
                'profession' => $member->profession,
                'idDocumentNumber' => $member->id_document_number,
                'photoUrl' => $member->photo_path ? Storage::disk('public')->url($member->photo_path) : null,
                'section' => $member->section,
                'referralSource' => $member->referral_source,
                'status' => $member->status,
                'note' => $member->note,
                'joinedAt' => $member->joined_at?->translatedFormat('d F Y'),
                'consentAt' => $member->consent_at->translatedFormat('d F Y, H:i'),
            ],
            'payments' => $member->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'tier' => $payment->tier,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'gateway' => $payment->gateway,
                'status' => $payment->status,
                'paidAt' => $payment->paid_at?->translatedFormat('d M Y, H:i'),
            ]),
        ]);
    }

    public function toggleStatus(Member $member): RedirectResponse
    {
        (new ToggleMemberStatus)->handle($member, Auth::user());

        // On nomme le NOUVEL état plutôt que de dire « mis à jour » : le bouton étant une
        // bascule, c'est la seule formulation qui lève le doute sans aller relire le badge.
        $message = $member->refresh()->status === 'active'
            ? "{$member->first_name} {$member->last_name} est de nouveau un adhérent actif."
            : "{$member->first_name} {$member->last_name} a été désactivé : sa carte de membre n'est plus valide.";

        return back()->with('success', $message);
    }

    public function updateNote(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:5000']]);

        $member->forceFill(['note' => $data['note'] ?? null])->save();

        activity()->causedBy(Auth::user())->performedOn($member)->log('Note interne mise à jour');

        return back()->with('success', 'Note interne enregistrée.');
    }

    public function resendCard(Member $member, ResendMembershipCard $resendMembershipCard): RedirectResponse
    {
        try {
            $resendMembershipCard->handle($member, Auth::user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Carte de membre renvoyée à {$member->email}.");
    }

    public function export(Request $request): StreamedResponse
    {
        $members = $this->filteredQuery($request)->orderBy('last_name')->get();

        return response()->streamDownload(function () use ($members) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Région', 'Commune', 'Section', 'Statut', 'Adhésion le']);

            foreach ($members as $member) {
                fputcsv($handle, [
                    $member->membership_code,
                    $member->first_name,
                    $member->last_name,
                    $member->email,
                    $member->phone,
                    $member->region,
                    $member->commune,
                    $member->section,
                    $member->status,
                    $member->joined_at?->format('Y-m-d') ?? '',
                ]);
            }

            fclose($handle);
        }, 'adherents-alsar-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        return Member::query()
            ->when($request->string('search')->trim()->isNotEmpty(), function (Builder $query) use ($request) {
                $search = $request->string('search')->trim()->value();
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('region'), fn (Builder $query) => $query->where('region', $request->string('region')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('section'), fn (Builder $query) => $query->where('section', $request->string('section')));
    }
}
