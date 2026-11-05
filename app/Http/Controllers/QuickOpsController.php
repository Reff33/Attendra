<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EventSession;
use App\Models\AttendanceRecord;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QuickOpsController extends Controller
{
    public function scanner()
    {
        $user = Auth::user();
        $sessions = EventSession::where('status', 'open')
            ->whereHas('activity', function ($q) use ($user) {
                if (!$user->isAdmin()) {
                    $q->where('user_id', $user->id);
                }
            })->get();

        return view('staff.quick-ops.scanner', compact('sessions'));
    }

    public function walkIn()
    {
        $user = Auth::user();
        $sessions = EventSession::where('status', 'open')
            ->whereHas('activity', function ($q) use ($user) {
                if (!$user->isAdmin()) {
                    $q->where('user_id', $user->id);
                }
            })->with('activity')->get();

        return view('staff.quick-ops.walk-in', compact('sessions'));
    }

    public function storeWalkIn(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:event_sessions,id',
            'student_name' => 'required|string|max:255',
            'matric_no' => 'required|string|max:50',
            'override_reason' => 'required|string|max:255',
        ]);

        $matricNo = strtoupper(trim($request->matric_no));
        $session = EventSession::findOrFail($request->session_id);

        // Enforce UNIQUE(event_session_id, matric_no) domain rule
        $existing = AttendanceRecord::where('event_session_id', $session->id)
            ->where('matric_no', $matricNo)
            ->first();

        if ($existing) {
            return back()->with('error', "Duplicate check-in blocked: Matric {$matricNo} is already registered for this session.");
        }

        // Check if student is in roster
        $participant = Participant::where('matric_no', $matricNo)
            ->whereHas('roster', function ($q) use ($session) {
                $q->where('activity_id', $session->activity_id);
            })->first();

        $tokenHash = 'ATT-' . strtoupper(Str::random(8));

        $record = AttendanceRecord::create([
            'event_session_id' => $session->id,
            'participant_id' => $participant?->id,
            'matric_no' => $matricNo,
            'student_name' => strtoupper($request->student_name),
            'verification_method' => 'manual_walkin',
            'status' => 'present',
            'is_walk_in' => true,
            'override_reason' => $request->override_reason,
            'token_hash' => $tokenHash,
            'checked_in_at' => now(),
        ]);

        // Audit Trail entry for manual override (PRD §2.1 V03)
        AuditLog::record(
            'MANUAL_OVERRIDE',
            'AttendanceRecord',
            (string) $record->id,
            "Manual walk-in registered for {$matricNo} ({$record->student_name}) by staff. Reason: {$request->override_reason}"
        );

        return back()->with('success', "Manual walk-in successfully authorized for {$record->student_name} ({$matricNo}). Token: {$tokenHash}");
    }

    public function handleScan(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:event_sessions,id',
            'qr_payload' => 'required|string',
        ]);

        $sessionId = (int) $request->session_id;
        $payload = trim($request->qr_payload);
        $session = EventSession::findOrFail($sessionId);

        // 1. Check if payload is an existing digital ticket token or pass URL
        $token = $payload;
        if (preg_match('/pass\/([A-Za-z0-9\-]+)/', $payload, $matches)) {
            $token = $matches[1];
        }

        $existingRecord = AttendanceRecord::where('token_hash', $token)->first();
        if ($existingRecord) {
            return response()->json([
                'status' => 'verified_existing',
                'title' => 'Digital Pass Sah Disahkan',
                'student_name' => $existingRecord->student_name,
                'matric_no' => $existingRecord->matric_no,
                'token_hash' => $existingRecord->token_hash,
                'method' => $existingRecord->verification_method,
                'time' => $existingRecord->checked_in_at->format('h:i:s A'),
                'is_same_session' => $existingRecord->event_session_id === $sessionId,
            ]);
        }

        // 2. If payload is a matric number from student card QR/Barcode (e.g. B032110045)
        $matricCandidate = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $payload)));

        // Check duplicate in current session
        $dup = AttendanceRecord::where('event_session_id', $session->id)
            ->where('matric_no', $matricCandidate)
            ->first();

        if ($dup) {
            return response()->json([
                'status' => 'already_checked_in',
                'title' => 'Student Already Checked In',
                'student_name' => $dup->student_name,
                'matric_no' => $dup->matric_no,
                'token_hash' => $dup->token_hash,
                'method' => $dup->verification_method,
                'time' => $dup->checked_in_at->format('h:i:s A'),
            ]);
        }

        // Check if exists in roster
        $participant = Participant::where('matric_no', $matricCandidate)
            ->whereHas('roster', function ($q) use ($session) {
                $q->where('activity_id', $session->activity_id);
            })->first();

        if (!$participant) {
            return response()->json([
                'status' => 'not_found',
                'title' => 'Matric No. Not Found in Roster',
                'message' => "Matric No. {$matricCandidate} is not in this session's roster. Please register via manual Walk-In.",
            ], 404);
        }

        // Register attendance via staff camera scan
        $tokenHash = 'SCAN-' . strtoupper(Str::random(8));
        $record = AttendanceRecord::create([
            'event_session_id' => $session->id,
            'participant_id' => $participant->id,
            'matric_no' => $participant->matric_no,
            'student_name' => $participant->name,
            'verification_method' => 'qr_staff_scan',
            'status' => 'present',
            'is_walk_in' => false,
            'token_hash' => $tokenHash,
            'checked_in_at' => now(),
        ]);

        AuditLog::record(
            'STAFF_CAMERA_SCAN',
            'AttendanceRecord',
            (string) $record->id,
            "Staff scanner verified {$record->matric_no} ({$record->student_name})"
        );

        return response()->json([
            'status' => 'checked_in_success',
            'title' => 'Attendance Recorded Successfully!',
            'student_name' => $record->student_name,
            'matric_no' => $record->matric_no,
            'token_hash' => $record->token_hash,
            'method' => 'Staff Camera Scan',
            'time' => $record->checked_in_at->format('h:i:s A'),
        ]);
    }
}
