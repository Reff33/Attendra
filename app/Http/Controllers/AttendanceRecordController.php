<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\EventSession;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceRecordController extends Controller
{
    /**
     * Override a record's status (PRD §2.1 / §6.1: manual correction with reason + audit).
     */
    public function correct(Request $request, EventSession $session, AttendanceRecord $record)
    {
        $this->authorizeSession($session);

        if ($record->event_session_id !== $session->id) {
            abort(404);
        }

        $data = $request->validate([
            'status' => 'required|in:present,late,excused,absent',
            'override_reason' => 'required|string|min:5|max:255',
        ]);

        $before = $record->status;

        if ($before === $data['status']) {
            return back()->with('error', "Record is already marked {$before}.");
        }

        $record->update([
            'status' => $data['status'],
            'override_reason' => $data['override_reason'],
        ]);

        AuditLog::record(
            'RECORD_CORRECTION',
            'AttendanceRecord',
            (string) $record->id,
            "Status {$before} -> {$data['status']} for {$record->matric_no} ({$record->student_name}). Reason: {$data['override_reason']}"
        );

        // An admin correcting someone else's session should tell the owner.
        $ownerId = $session->activity?->user_id;
        if ($ownerId && $ownerId !== Auth::id()) {
            UserNotification::notify(
                $ownerId,
                'Attendance record corrected',
                "{$record->student_name} ({$record->matric_no}) was changed from {$before} to {$data['status']} on \"{$session->title}\".",
                'record',
                route('matrix.index', ['activity_id' => $session->activity_id]),
                'high'
            );
        }

        return back()->with('success', "Record for {$record->student_name} updated to {$data['status']}.");
    }

    /**
     * Remove a record so the student can check in again.
     */
    public function destroy(Request $request, EventSession $session, AttendanceRecord $record)
    {
        $this->authorizeSession($session);

        if ($record->event_session_id !== $session->id) {
            abort(404);
        }

        $data = $request->validate([
            'override_reason' => 'required|string|min:5|max:255',
        ]);

        $snapshot = "{$record->matric_no} ({$record->student_name}), status {$record->status}";
        $recordId = $record->id;
        $record->delete();

        AuditLog::record(
            'RECORD_DELETE',
            'AttendanceRecord',
            (string) $recordId,
            "Deleted attendance record for {$snapshot}. Reason: {$data['override_reason']}"
        );

        return back()->with('success', 'Attendance record removed. The student may check in again.');
    }

    /**
     * Staff may only correct records on their own activities; admins oversee everything.
     */
    private function authorizeSession(EventSession $session): void
    {
        $user = Auth::user();

        if (! $user->isAdmin() && $session->activity?->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this session.');
        }
    }
}
