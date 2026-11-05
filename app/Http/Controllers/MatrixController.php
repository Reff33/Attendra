<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AttendanceRecord;
use App\Models\EventSession;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatrixController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $activities = $user->isAdmin()
            ? Activity::with(['sessions', 'activeRoster'])->get()
            : Activity::where('user_id', $user->id)->with(['sessions', 'activeRoster'])->get();

        $selectedActivityId = $request->get('activity_id', $activities->first()?->id);
        $selectedActivity = $activities->firstWhere('id', $selectedActivityId);

        $sessions = collect();
        $participants = collect();
        $attendanceMap = [];

        if ($selectedActivity) {
            $sessions = $selectedActivity->sessions()->orderBy('starts_at')->get();

            if ($selectedActivity->activeRoster) {
                $participants = Participant::where('roster_id', $selectedActivity->activeRoster->id)
                    ->orderBy('name')
                    ->get();

                // Map attendance per matric_no and session_id
                $records = AttendanceRecord::whereIn('event_session_id', $sessions->pluck('id'))
                    ->get();

                foreach ($records as $r) {
                    $attendanceMap[$r->matric_no][$r->event_session_id] = $r;
                }
            }
        }

        return view('staff.matrix.index', compact(
            'activities',
            'selectedActivity',
            'sessions',
            'participants',
            'attendanceMap'
        ));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $activityId = $request->get('activity_id');

        $activity = $user->isAdmin()
            ? Activity::with(['sessions', 'activeRoster'])->findOrFail($activityId)
            : Activity::where('user_id', $user->id)->with(['sessions', 'activeRoster'])->findOrFail($activityId);

        $sessions = $activity->sessions()->orderBy('starts_at')->get();
        $participants = $activity->activeRoster
            ? Participant::where('roster_id', $activity->activeRoster->id)->orderBy('name')->get()
            : collect();

        $attendanceMap = [];
        if ($participants->isNotEmpty() && $sessions->isNotEmpty()) {
            $records = AttendanceRecord::whereIn('event_session_id', $sessions->pluck('id'))->get();
            foreach ($records as $r) {
                $attendanceMap[$r->matric_no][$r->event_session_id] = $r;
            }
        }

        $filename = "Attendra_Matrix_" . str_replace(' ', '_', $activity->title) . "_" . date('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($sessions, $participants, $attendanceMap) {
            $file = fopen('php://output', 'w');

            // Header Row
            $header = ['Matric No', 'Student Name', 'Program'];
            foreach ($sessions as $s) {
                $header[] = $s->title . ' (' . ($s->starts_at?->format('d/m/Y') ?? 'N/A') . ')';
            }
            $header[] = 'Total Attended';
            $header[] = 'Attendance Rate (%)';

            fputcsv($file, $header);

            $sessionCount = $sessions->count();

            foreach ($participants as $p) {
                $attended = 0;
                $row = [
                    $p->matric_no,
                    $p->name,
                    $p->program ?? 'N/A',
                ];

                foreach ($sessions as $s) {
                    $record = $attendanceMap[$p->matric_no][$s->id] ?? null;
                    if ($record) {
                        $attended++;
                        $row[] = $record->is_walk_in ? 'PRESENT (WALK-IN)' : 'PRESENT';
                    } else {
                        $row[] = 'ABSENT';
                    }
                }

                $rate = $sessionCount > 0 ? round(($attended / $sessionCount) * 100, 1) : 0;
                $row[] = $attended . ' / ' . $sessionCount;
                $row[] = $rate . '%';

                fputcsv($file, $row);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
