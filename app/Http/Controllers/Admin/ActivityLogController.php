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

        if (! $subject) {
            return '—';
        }

        return method_exists($subject, 'fullName') ? $subject->fullName() : "#{$subject->getKey()}";
    }
}
