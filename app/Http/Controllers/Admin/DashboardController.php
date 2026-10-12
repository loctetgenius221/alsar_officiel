<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Models\Article;
use App\Domain\Members\Models\Member;
use App\Domain\Payments\Models\MembershipPayment;
use App\Http\Controllers\Controller;
use App\Support\AdminRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardController extends Controller
{
    /** Nombre de mois affichés sur les graphiques de tendance (adhérents, montant collecté). */
    private const TREND_MONTHS = 6;

    /**
     * Le dashboard est le seul écran commun à tous les rôles : il se compose donc des blocs que
     * l'utilisateur a le droit de voir, et rien de plus. Un rédacteur n'y lit pas le nombre
     * d'adhérents — et le serveur ne l'envoie même pas, plutôt que de compter sur le front
     * pour masquer une donnée déjà transmise.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();

        return Inertia::render('Admin/Dashboard', [
            ...($user->can(AdminRoles::MANAGE_MEMBERS) ? $this->membershipPayload() : []),
            ...($user->can(AdminRoles::MANAGE_ARTICLES) ? ['contentKpis' => $this->contentPayload()] : []),
        ]);
    }

    /**
     * Blocs adhérents/cotisations (permission members.manage).
     *
     * @return array<string, mixed>
     */
    private function membershipPayload(): array
    {
        $totalMembers = Member::count();
        $activeMembers = Member::where('status', 'active')->count();

        $latestTransactions = MembershipPayment::with('member')
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(fn (MembershipPayment $payment) => [
                'publicId' => $payment->getRouteKey(),
                'memberName' => $payment->member?->fullName() ?? '—',
                'tier' => $payment->tier,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'createdAt' => $payment->created_at->translatedFormat('d M Y, H:i'),
            ]);

        $regionBreakdown = Member::selectRaw('region, count(*) as total')
            ->groupBy('region')
            ->orderByDesc('total')
            ->get();
        $maxRegionCount = (int) ($regionBreakdown->max('total') ?? 0);

        return [
            'kpis' => [
                'newMembers30d' => Member::where('created_at', '>=', now()->subDays(30))->count(),
                'totalCollected' => (int) MembershipPayment::where('status', 'success')->sum('amount'),
                'currency' => config('adhesion.currency'),
                'paidMembershipRate' => $totalMembers > 0
                    ? round(($activeMembers / $totalMembers) * 100)
                    : 0,
                'totalMembers' => $totalMembers,
            ],
            'latestTransactions' => $latestTransactions,
            'regionBreakdown' => $regionBreakdown->map(fn ($row) => [
                'region' => $row->region,
                'total' => $row->total,
            ]),
            'memberTrend' => $this->monthlyTrend(
                Member::query(),
                'created_at',
            ),
            'revenueTrend' => $this->monthlyTrend(
                MembershipPayment::where('status', 'success'),
                'paid_at',
                sumColumn: 'amount',
            ),
            'tierBreakdown' => $this->tierBreakdown(),
            'paymentStatusBreakdown' => MembershipPayment::selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ];
    }

    /**
     * Bloc contenu (permission articles.manage) : l'essentiel pour un rédacteur, c'est de voir
     * d'un coup d'œil ce qui est en ligne, ce qui attend sa date, et ce qui dort en brouillon.
     *
     * @return array<string, mixed>
     */
    private function contentPayload(): array
    {
        return [
            'published' => Article::whereNotNull('published_at')->where('published_at', '<=', now())->count(),
            'scheduled' => Article::where('published_at', '>', now())->count(),
            'drafts' => Article::whereNull('published_at')->count(),
            'latest' => Article::latest('updated_at')
                ->take(5)
                ->get()
                ->map(fn (Article $article) => [
                    'publicId' => $article->getRouteKey(),
                    'title' => $article->title ?: 'Sans titre',
                    'status' => $article->publicationStatus(),
                    'updatedAt' => $article->updated_at->translatedFormat('d M Y'),
                ]),
        ];
    }

    /**
     * Compte (ou somme) mensuel(le) sur une fenêtre glissante de TREND_MONTHS mois, y compris
     * les mois sans aucune ligne — un graphique de tendance avec un trou silencieux se lirait
     * comme un bug plutôt que comme "zéro ce mois-là". Regroupement fait en PHP (Carbon)
     * plutôt qu'avec une fonction de date SQL spécifique au moteur (`strftime` SQLite vs
     * `DATE_FORMAT` MySQL) — l'app tourne sur MySQL en dev/prod mais sqlite en test
     * (cf. CLAUDE.md), et la fenêtre ne porte de toute façon que sur quelques mois de lignes.
     *
     * @return array<int, array{label: string, value: int}>
     */
    private function monthlyTrend(Builder $query, string $dateColumn, string $sumColumn = ''): array
    {
        $from = now()->startOfMonth()->subMonths(self::TREND_MONTHS - 1);

        $rows = (clone $query)
            ->where($dateColumn, '>=', $from)
            ->get([$dateColumn, ...($sumColumn !== '' ? [$sumColumn] : [])])
            ->groupBy(fn ($row) => $row->{$dateColumn}->format('Y-m'))
            ->map(fn (Collection $group) => $sumColumn !== '' ? (int) $group->sum($sumColumn) : $group->count());

        $months = collect(range(0, self::TREND_MONTHS - 1))->map(fn (int $i) => $from->copy()->addMonths($i));

        return $months->map(fn (Carbon $month) => [
            'label' => $month->translatedFormat('M'),
            'value' => (int) ($rows[$month->format('Y-m')] ?? 0),
        ])->all();
    }

    /**
     * Répartition des cotisations réussies par palier — dans l'ordre du montant (standard <
     * soutien < bienfaiteur), une échelle ordinale plutôt qu'une identité catégorielle
     * arbitraire (cf. config('adhesion.tiers') qui reste la source d'autorité des montants).
     *
     * @return array<int, array{tier: string, label: string, total: int, amount: int}>
     */
    private function tierBreakdown(): Collection
    {
        $counts = MembershipPayment::where('status', 'success')
            ->selectRaw('tier, count(*) as total')
            ->groupBy('tier')
            ->pluck('total', 'tier');

        return collect(config('adhesion.tiers'))
            ->map(fn (array $tier, string $key) => [
                'tier' => $key,
                'label' => $tier['label'],
                'amount' => $tier['amount'],
                'total' => (int) ($counts[$key] ?? 0),
            ])
            ->values();
    }
}
