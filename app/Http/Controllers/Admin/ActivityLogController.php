<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(): InertiaResponse
    {
        $activities = Activity::with(['causer', 'subject'])
            ->latest('created_at')
            ->paginate(30)
            ->through(fn (Activity $activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'causerName' => $activity->causer?->name ?? 'Système',
                'subjectType' => class_basename($activity->subject_type),
                'subjectLabel' => $this->subjectLabel($activity),
                'properties' => $activity->properties->isNotEmpty() ? $activity->properties->toArray() : null,
                'createdAt' => $activity->created_at->translatedFormat('d M Y, H:i'),
            ]);

        return Inertia::render('Admin/ActivityLog/Index', [
            'activities' => $activities,
        ]);
    }

    private function subjectLabel(Activity $activity): string
    {
        $subject = $activity->subject;

        // L'objet a été supprimé depuis : on le dit, au lieu d'afficher un tiret qui laisse
        // croire à une donnée manquante. C'est une information utile dans un journal d'audit.
        if (! $subject) {
            return 'supprimé depuis';
        }

        // On nomme l'objet concerné plutôt que d'afficher « #12 » : un numéro de ligne n'aide
        // personne à relire un journal d'audit, et c'est précisément la clé primaire qu'on a
        // cessé d'exposer ailleurs (cf. App\Support\HasPublicId). En dernier recours, on
        // retombe sur l'identifiant public, opaque lui aussi.
        return match (true) {
            method_exists($subject, 'fullName') => $subject->fullName(),
            filled($subject->title ?? null) => $subject->title,
            filled($subject->name ?? null) => $subject->name,
            filled($subject->label ?? null) => $subject->label,
            default => (string) $subject->getRouteKey(),
        };
    }
}
