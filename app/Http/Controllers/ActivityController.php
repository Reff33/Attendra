<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $activities = ($user->isAdmin() ? Activity::query() : Activity::where('user_id', $user->id))
            ->with(['activeRoster'])
            ->withCount('sessions')
            ->latest()
            ->paginate(15);

        return view('staff.activities.index', compact('activities'));
    }

    public function create()
    {
        return view('staff.activities.form', ['activity' => new Activity()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $activity = Activity::create([
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'faculty' => $data['faculty'] ?? Auth::user()->faculty,
            'description' => $data['description'] ?? null,
            'status' => 'active',
        ]);

        AuditLog::record(
            'ACTIVITY_CREATE',
            'Activity',
            (string) $activity->id,
            "Created activity '{$activity->title}' ({$activity->faculty})"
        );

        return redirect()->route('activities.index')
            ->with('success', "Activity '{$activity->title}' created. Upload a roster next.");
    }

    public function edit(Activity $activity)
    {
        $this->authorizeActivity($activity);

        return view('staff.activities.form', compact('activity'));
    }

    public function update(Request $request, Activity $activity)
    {
        $this->authorizeActivity($activity);

        $data = $this->validated($request);

        $activity->update([
            'title' => $data['title'],
            'faculty' => $data['faculty'] ?? $activity->faculty,
            'description' => $data['description'] ?? null,
        ]);

        AuditLog::record(
            'ACTIVITY_UPDATE',
            'Activity',
            (string) $activity->id,
            "Updated activity '{$activity->title}'"
        );

        return redirect()->route('activities.index')->with('success', 'Activity updated.');
    }

    public function toggleStatus(Activity $activity)
    {
        $this->authorizeActivity($activity);

        $newStatus = $activity->status === 'archived' ? 'active' : 'archived';
        $activity->update(['status' => $newStatus]);

        AuditLog::record(
            $newStatus === 'archived' ? 'ACTIVITY_ARCHIVE' : 'ACTIVITY_RESTORE',
            'Activity',
            (string) $activity->id,
            "Activity '{$activity->title}' set to {$newStatus}"
        );

        return back()->with('success', "Activity '{$activity->title}' is now {$newStatus}.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'faculty' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'activity';
        $slug = $base;
        $i = 2;

        while (Activity::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * Staff may only manage their own activities; admins oversee everything (Domain Rule 1).
     */
    private function authorizeActivity(Activity $activity): void
    {
        $user = Auth::user();

        if (! $user->isAdmin() && $activity->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this activity.');
        }
    }
}
