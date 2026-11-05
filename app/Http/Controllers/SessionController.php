<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\EventSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $sessionsQuery = EventSession::with(['activity', 'attendanceRecords'])
            ->whereHas('activity', function ($q) use ($user) {
                if (!$user->isAdmin()) {
                    $q->where('user_id', $user->id);
                }
            })
            ->latest();

        $sessions = $sessionsQuery->get();
        $liveSession = $sessions->firstWhere('status', 'open');

        return view('staff.sessions.index', compact('sessions', 'liveSession'));
    }

    public function create()
    {
        $user = Auth::user();
        $activities = $user->isAdmin()
            ? Activity::with('rosters')->get()
            : Activity::where('user_id', $user->id)->with('rosters')->get();

        return view('staff.sessions.create', compact('activities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'title' => 'required|string|max:255',
            'venue' => 'required|string|max:255',
            'verification_mode' => 'required|in:none,ic_last_4,gps,ic_and_gps',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'geofence_radius_meters' => 'nullable|integer',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $session = EventSession::create([
            'activity_id' => $request->activity_id,
            'title' => $request->title,
            'venue' => $request->venue,
            'access_token' => Str::slug($request->title) . '-' . Str::random(6),
            'status' => 'open', // Open by default
            'verification_mode' => $request->verification_mode,
            'latitude' => $request->latitude ?? 2.313889,
            'longitude' => $request->longitude ?? 102.319444,
            'geofence_radius_meters' => $request->geofence_radius_meters ?? 100,
            'starts_at' => $request->starts_at ?? now(),
            'ends_at' => $request->ends_at ?? now()->addHours(3),
        ]);

        AuditLog::record(
            'SESSION_CREATE',
            'EventSession',
            (string) $session->id,
            "Created session '{$session->title}' at {$session->venue}"
        );

        return redirect()->route('sessions.index')->with('success', 'New attendance session created & broadcasted successfully.');
    }

    public function toggleStatus(EventSession $session)
    {
        $newStatus = $session->status === 'open' ? 'closed' : 'open';
        $session->update(['status' => $newStatus]);

        AuditLog::record(
            $newStatus === 'open' ? 'SESSION_OPEN' : 'SESSION_CLOSE',
            'EventSession',
            (string) $session->id,
            "Session status changed to {$newStatus}"
        );

        return back()->with('success', "Session is now {$newStatus}.");
    }

    public function duplicate(EventSession $session)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $session->activity->user_id !== $user->id) {
            abort(403, 'Unauthorized access to session.');
        }

        $newTitle = $session->title . ' (Copy)';
        $newSession = EventSession::create([
            'activity_id' => $session->activity_id,
            'title' => $newTitle,
            'venue' => $session->venue,
            'access_token' => Str::slug($newTitle) . '-' . Str::random(6),
            'status' => 'open',
            'verification_mode' => $session->verification_mode,
            'latitude' => $session->latitude,
            'longitude' => $session->longitude,
            'geofence_radius_meters' => $session->geofence_radius_meters,
            'starts_at' => now(),
            'ends_at' => now()->addHours(3),
        ]);

        AuditLog::record(
            'SESSION_DUPLICATE',
            'EventSession',
            (string) $newSession->id,
            "Duplicated session from '{$session->title}' to '{$newSession->title}'"
        );

        return redirect()->route('sessions.index')->with('success', "Session successfully duplicated as '{$newSession->title}'.");
    }

    public function broadcast(EventSession $session)
    {
        $session->load(['activity.rosters.participants', 'attendanceRecords']);

        $totalRoster = $session->activity->rosters->sum('total_records');
        if ($totalRoster === 0) {
            $totalRoster = $session->activity->rosters->flatMap->participants->count();
        }
        $checkedInCount = $session->attendanceRecords->count();
        $recentRecords = $session->attendanceRecords()->latest('checked_in_at')->take(5)->get();

        return view('staff.sessions.broadcast', compact('session', 'totalRoster', 'checkedInCount', 'recentRecords'));
    }

    public function liveStats(EventSession $session)
    {
        $checkedInCount = $session->attendanceRecords()->count();
        $totalRoster = $session->activity->rosters->sum('total_records');
        if ($totalRoster === 0) {
            $totalRoster = $session->activity->rosters->flatMap->participants->count();
        }
        if ($totalRoster === 0) {
            $totalRoster = 450;
        }

        $rate = $totalRoster > 0 ? round(($checkedInCount / $totalRoster) * 100, 1) : 0;

        $recent = $session->attendanceRecords()
            ->latest('checked_in_at')
            ->take(8)
            ->get()
            ->map(function ($r) use ($session) {
                return [
                    'student_name' => $r->student_name,
                    'matric_no' => $r->matric_no,
                    'venue' => ($session->activity->faculty ?? 'FTMK') . ' • ' . $session->venue,
                    'checked_in_at' => $r->checked_in_at ? $r->checked_in_at->format('h:i:s A') : 'Just now',
                    'verification_method' => $r->is_walk_in ? 'Walk-In Override' : ($r->verification_method ?? 'Venue QR'),
                    'is_walk_in' => (bool) $r->is_walk_in,
                    'token_hash' => $r->token_hash,
                ];
            });

        return response()->json([
            'status' => $session->status,
            'checked_in_count' => $checkedInCount,
            'total_roster' => $totalRoster,
            'rate' => $rate,
            'recent' => $recent,
        ]);
    }

    public function export(EventSession $session)
    {
        $records = $session->attendanceRecords()->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"Attendra_{$session->access_token}_attendance.csv\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Student Name', 'Matric No', 'Status', 'Verification Method', 'Checked In At', 'Token Hash']);

            foreach ($records as $r) {
                fputcsv($file, [
                    $r->id,
                    $r->student_name,
                    $r->matric_no,
                    $r->status,
                    $r->verification_method,
                    $r->checked_in_at->format('Y-m-d H:i:s'),
                    $r->token_hash,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
