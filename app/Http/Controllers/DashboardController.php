<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AttendanceRecord;
use App\Models\EventSession;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Isolation Rule: Staff sees only their activities, Admin oversight sees all (PRD §5.2)
        $activitiesQuery = $user->isAdmin()
            ? Activity::query()
            : Activity::where('user_id', $user->id);

        $activities = $activitiesQuery->with(['sessions', 'activeRoster'])->latest()->get();

        // Active Live Session (Dewan Al-Jazari)
        $liveSession = EventSession::where('status', 'open')
            ->whereHas('activity', function ($q) use ($user) {
                if (!$user->isAdmin()) {
                    $q->where('user_id', $user->id);
                }
            })->with('activity')->first();

        // Live stats for session
        $totalRegistered = 450;
        $checkedInCount = 0;
        $presentRecords = collect();

        if ($liveSession) {
            $totalRoster = $liveSession->activity->rosters->sum('total_records');
            if ($totalRoster === 0) {
                $totalRoster = $liveSession->activity->rosters->flatMap->participants->count();
            }
            if ($totalRoster > 0) {
                $totalRegistered = $totalRoster;
            }

            $checkedInCount = AttendanceRecord::where('event_session_id', $liveSession->id)->count();
            $presentRecords = AttendanceRecord::where('event_session_id', $liveSession->id)
                ->latest('checked_in_at')
                ->take(10)
                ->get();
        }

        $rate = $totalRegistered > 0 ? round(($checkedInCount / $totalRegistered) * 100, 1) : 0;

        $initialRecords = $presentRecords->map(function ($r) use ($liveSession) {
            return [
                'student_name' => $r->student_name,
                'matric_no' => $r->matric_no,
                'venue' => ($liveSession?->activity->faculty ?? 'FTMK') . ' • ' . ($liveSession?->venue ?? 'Venue'),
                'checked_in_at' => $r->checked_in_at ? $r->checked_in_at->format('h:i:s A') : 'Just now',
                'verification_method' => $r->is_walk_in ? 'Walk-In Override' : ($r->verification_method ?? 'Venue QR'),
                'is_walk_in' => (bool) $r->is_walk_in,
                'token_hash' => $r->token_hash,
            ];
        });

        return view('staff.dashboard', compact(
            'user',
            'activities',
            'liveSession',
            'totalRegistered',
            'checkedInCount',
            'rate',
            'presentRecords',
            'initialRecords'
        ));
    }
}
