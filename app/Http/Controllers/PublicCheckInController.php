<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\EventSession;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicCheckInController extends Controller
{
    public function show($token)
    {
        $session = EventSession::where('access_token', $token)->with('activity.activeRoster')->firstOrFail();

        return view('public.checkin', compact('session'));
    }

    public function searchRoster(Request $request, $token)
    {
        $session = EventSession::where('access_token', $token)->firstOrFail();
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json(['participants' => []]);
        }

        $participants = Participant::whereHas('roster', function ($q) use ($session) {
            $q->where('activity_id', $session->activity_id);
        })
        ->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('matric_no', 'like', "%{$query}%");
        })
        ->take(10)
        ->get(['id', 'name', 'matric_no', 'program']);

        return response()->json(['participants' => $participants]);
    }

    public function submit(Request $request, $token)
    {
        $session = EventSession::where('access_token', $token)->firstOrFail();

        if ($session->status !== 'open') {
            return back()->with('error', 'This attendance session has already closed.');
        }

        // Domain Rule 2: Server time (Asia/Kuala_Lumpur) governs session validity
        if ($session->ends_at && now()->greaterThan($session->ends_at)) {
            if ($session->status === 'open') {
                $session->update(['status' => 'closed']);
            }
            return back()->with('error', 'This attendance session ended at ' . $session->ends_at->format('h:i A') . '. Check-in is now closed.');
        }

        if ($session->starts_at && now()->lessThan($session->starts_at)) {
            return back()->with('error', 'This attendance session is not open yet. Check-in starts at ' . $session->starts_at->format('h:i A') . '.');
        }

        $request->validate([
            'matric_no' => 'required|string',
            'ic_last_4' => 'nullable|string|digits:4',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $matricNo = strtoupper(trim($request->matric_no));

        // Check if student is in roster
        $participant = Participant::where('matric_no', $matricNo)
            ->whereHas('roster', function ($q) use ($session) {
                $q->where('activity_id', $session->activity_id);
            })->first();

        if (!$participant) {
            return back()->with('error', "Matric number {$matricNo} is not listed on this event's roster. Please see staff for manual walk-in.");
        }

        // 1. Duplicate check (Enforced in DB UNIQUE(event_session_id, matric_no))
        $existing = AttendanceRecord::where('event_session_id', $session->id)
            ->where('matric_no', $matricNo)
            ->first();

        if ($existing) {
            return redirect()->route('public.pass', ['token' => $token, 'hash' => $existing->token_hash])
                ->with('info', 'You are already checked in for this session.');
        }

        // 2. Anti-Proxy Policy: 4-digit IC Verification
        if (in_array($session->verification_mode, ['ic_last_4', 'ic_and_gps'])) {
            if (!$request->ic_last_4 || $request->ic_last_4 !== $participant->ic_last_4) {
                return back()->with('error', 'IC 4-Digit verification failed. Last 4 digits do not match university records.');
            }
        }

        // 3. Anti-Proxy Policy: GPS Geofence
        $distance = null;
        $studentLat = $request->latitude ? (float) $request->latitude : null;
        $studentLng = $request->longitude ? (float) $request->longitude : null;
        $verificationMethod = $session->verification_mode;

        if (in_array($session->verification_mode, ['gps', 'ic_and_gps'])) {
            if (!$studentLat || !$studentLng) {
                return back()->with('error', 'GPS verification required. Please enable location permissions on your mobile device.');
            }

            if ($session->latitude && $session->longitude) {
                $distance = $this->haversineGreatCircleDistance(
                    (float) $session->latitude,
                    (float) $session->longitude,
                    $studentLat,
                    $studentLng
                );

                $allowedRadius = (int) ($session->geofence_radius_meters ?? 100);

                if ($distance > $allowedRadius) {
                    return back()->with('error', "GPS Geofence check failed. You are {$distance}m away from {$session->venue} (Allowed: {$allowedRadius}m).");
                }
            }
        }

        // Generate tamper-proof pass token
        $tokenHash = 'ATT-' . strtoupper(Str::random(10));

        $record = AttendanceRecord::create([
            'event_session_id' => $session->id,
            'participant_id' => $participant->id,
            'matric_no' => $participant->matric_no,
            'student_name' => $participant->name ?? $participant->student_name,
            'verification_method' => $verificationMethod,
            'status' => 'present',
            'is_walk_in' => false,
            'recorded_latitude' => $studentLat,
            'recorded_longitude' => $studentLng,
            'distance_meters' => $distance,
            'token_hash' => $tokenHash,
            'checked_in_at' => now(),
        ]);

        AuditLog::record(
            'STUDENT_CHECKIN',
            'AttendanceRecord',
            (string) $record->id,
            "Student {$record->student_name} ({$record->matric_no}) checked in via {$verificationMethod}" . ($distance !== null ? " ({$distance}m)" : "")
        );

        return redirect()->route('public.pass', ['token' => $token, 'hash' => $tokenHash])
            ->with('success', 'Attendance verified and recorded successfully!');
    }

    private function haversineGreatCircleDistance(float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo, int $earthRadius = 6371000): float
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 1);
    }

    public function pass($token, $hash)
    {
        $session = EventSession::where('access_token', $token)->firstOrFail();
        $record = AttendanceRecord::where('event_session_id', $session->id)
            ->where('token_hash', $hash)
            ->firstOrFail();

        return view('public.pass', compact('session', 'record'));
    }
}
