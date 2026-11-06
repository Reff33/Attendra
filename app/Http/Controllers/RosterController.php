<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\Roster;
use App\Models\UserNotification;
use App\Services\BulkImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RosterController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $activities = $user->isAdmin()
            ? Activity::with('activeRoster')->get()
            : Activity::where('user_id', $user->id)->with('activeRoster')->get();

        $selectedActivityId = $request->get('activity_id', $activities->first()?->id);
        $selectedActivity = $activities->firstWhere('id', $selectedActivityId);

        $participants = collect();
        if ($selectedActivity && $selectedActivity->activeRoster) {
            $participants = Participant::where('roster_id', $selectedActivity->activeRoster->id)
                ->orderBy('name')
                ->paginate(15);
        }

        return view('staff.rosters.index', compact('activities', 'selectedActivity', 'participants'));
    }

    /**
     * Preview a roster from an uploaded file OR a pasted Google Sheets link.
     * Returns detected headers and sample rows (Enterprise Step 1 & 2).
     */
    public function preview(Request $request, BulkImportService $importService): JsonResponse
    {
        $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'roster_file' => 'required_without:sheet_url|nullable|file|mimes:csv,txt,xlsx|max:10240',
            'sheet_url' => 'required_without:roster_file|nullable|string|max:2048',
        ]);

        $user = Auth::user();
        $activity = Activity::findOrFail($request->activity_id);

        if (!$user->isAdmin() && $activity->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to activity.'], 403);
        }

        try {
            if ($request->filled('sheet_url')) {
                [$content, $filename] = $this->fetchSheet($request->input('sheet_url'));
            } else {
                $file = $request->file('roster_file');
                $content = file_get_contents($file->getRealPath());
                $filename = $file->getClientOriginalName();
            }

            if ($content === false || trim($content) === '') {
                return response()->json(['success' => false, 'message' => 'The roster source is empty.'], 422);
            }

            $allRows = $importService->parse($content, $filename);
            if (empty($allRows)) {
                return response()->json(['success' => false, 'message' => 'No readable data rows found in the uploaded file.'], 422);
            }

            // Headers are row 0
            $headerRow = $allRows[0];
            $headers = [];
            foreach ($headerRow as $idx => $headerName) {
                $trimmed = trim((string) $headerName);
                $headers[] = [
                    'index' => $idx,
                    'label' => $trimmed !== '' ? $trimmed : ('Column ' . ($idx + 1)),
                ];
            }

            // Sample rows starting from row 1 (max 10 rows for preview)
            $sampleRows = array_slice($allRows, 1, 10);
            $totalDataRows = max(0, count($allRows) - 1);
            $detected = $importService->detectColumns($headers);

            return response()->json([
                'success'         => true,
                'file_name'       => $filename,
                'headers'         => $headers,
                'sample_rows'     => $sampleRows,
                'detected'        => $detected,
                'total_data_rows' => $totalDataRows,
                'raw_content'     => base64_encode($content),
            ]);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse roster: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Turn a Google Sheets share link into a CSV export download.
     *
     * The host is hardcoded and only the sheet ID is taken from user input, so this
     * cannot be pointed at an arbitrary URL (no SSRF surface).
     *
     * @return array{0: string, 1: string} [content, filename]
     */
    private function fetchSheet(string $url): array
    {
        if (! preg_match('#/spreadsheets/d/([A-Za-z0-9_-]+)#', $url, $match)) {
            throw new \InvalidArgumentException(
                'That does not look like a Google Sheets link. It should contain /spreadsheets/d/<sheet-id>.'
            );
        }

        $sheetId = $match[1];
        $gid = preg_match('/[#&?]gid=(\d+)/', $url, $g) ? $g[1] : '0';
        $exportUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";

        $response = Http::timeout(20)->get($exportUrl);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Could not read the sheet. In Google Sheets choose Share → General access → "Anyone with the link" and try again.'
            );
        }

        $body = $response->body();

        // A private sheet answers with the Google sign-in page instead of CSV.
        if (stripos(substr($body, 0, 2000), '<html') !== false) {
            throw new \RuntimeException(
                'The sheet is not publicly readable. Set sharing to "Anyone with the link" and try again.'
            );
        }

        return [$body, 'google-sheet-' . $sheetId . '.csv'];
    }

    /**
     * Execute bulk import using column mapping with DB Transaction, sanitization & audit logs.
     */
    public function import(Request $request, BulkImportService $importService)
    {
        $request->validate([
            'activity_id'  => 'required|exists:activities,id',
            'raw_content'  => 'required|string',
            'filename'     => 'nullable|string',
            'col_name'     => 'required|integer',
            'col_matric'   => 'required|integer',
            'col_ic'       => 'nullable|integer',
            'col_email'    => 'nullable|integer',
            'col_program'  => 'nullable|integer',
        ]);

        $user = Auth::user();
        $activity = Activity::findOrFail($request->activity_id);

        if (!$user->isAdmin() && $activity->user_id !== $user->id) {
            abort(403, 'Unauthorized access to activity roster.');
        }

        $csvContent = base64_decode($request->raw_content);
        if ($csvContent === false || trim($csvContent) === '') {
            return back()->with('error', 'Invalid CSV payload data.');
        }

        $allRows = $importService->parse($csvContent, $request->input('filename'));
        if (count($allRows) <= 1) {
            return back()->with('error', 'The uploaded file contains no data rows.');
        }

        $dataRows = array_slice($allRows, 1);
        $colName = (int) $request->col_name;
        $colMatric = (int) $request->col_matric;
        $colIc = $request->filled('col_ic') && $request->col_ic !== '' ? (int) $request->col_ic : null;
        $colEmail = $request->filled('col_email') && $request->col_email !== '' ? (int) $request->col_email : null;
        $colProgram = $request->filled('col_program') && $request->col_program !== '' ? (int) $request->col_program : null;

        $processedStudents = [];
        $skippedCount = 0;
        $seenMatrics = [];

        foreach ($dataRows as $lineIdx => $row) {
            $rawName = trim((string) ($row[$colName] ?? ''));
            $rawMatric = strtoupper(trim((string) ($row[$colMatric] ?? '')));

            // Skip empty key identifiers
            if ($rawName === '' || $rawMatric === '') {
                $skippedCount++;
                continue;
            }

            // Deduplicate within the same uploaded CSV file
            if (isset($seenMatrics[$rawMatric])) {
                $skippedCount++;
                continue;
            }
            $seenMatrics[$rawMatric] = true;

            // Extract last 4 digits of IC if present
            $rawIc = $colIc !== null ? trim((string) ($row[$colIc] ?? '')) : '';
            // Remove dashes or spaces from IC
            $cleanIc = preg_replace('/[^0-9]/', '', $rawIc);
            $icLast4 = $cleanIc !== '' ? substr($cleanIc, -4) : null;

            $email = $colEmail !== null ? trim((string) ($row[$colEmail] ?? '')) : null;
            $program = $colProgram !== null ? trim((string) ($row[$colProgram] ?? '')) : null;

            $processedStudents[] = [
                'name'      => $rawName,
                'matric_no' => $rawMatric,
                'ic_last_4' => $icLast4,
                'email'     => $email ?: null,
                'program'   => $program ?: ($activity->faculty ?? 'FTMK'),
            ];
        }

        if (empty($processedStudents)) {
            return back()->with('error', 'No valid participant rows could be extracted from mapping.');
        }

        // DB Transaction for atomic roster commit (PRD §2.1 R03 & CLAUDE.md §4)
        $rosterFileName = $request->input('filename') ?: 'imported_roster_' . date('Ymd_His') . '.csv';
        $createdCount = 0;

        DB::transaction(function () use ($activity, $processedStudents, $rosterFileName, &$createdCount) {
            $version = ($activity->rosters()->max('version') ?? 0) + 1;

            $roster = Roster::create([
                'activity_id'   => $activity->id,
                'filename'      => $rosterFileName,
                'dataset_label' => "Cohort Roster v{$version} (" . count($processedStudents) . " students)",
                'total_records' => count($processedStudents),
                'is_frozen'     => true,
            ]);

            // Clear previous participants for this activity to maintain UNIQUE(activity_id, matric_no) constraint
            Participant::where('activity_id', $activity->id)->delete();

            $insertBatch = [];
            foreach ($processedStudents as $st) {
                $insertBatch[] = [
                    'roster_id'   => $roster->id,
                    'activity_id' => $activity->id,
                    'name'        => $st['name'],
                    'matric_no'   => $st['matric_no'],
                    'ic_last_4'   => $st['ic_last_4'],
                    'email'       => $st['email'],
                    'program'     => $st['program'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
                $createdCount++;
            }

            // Streamed chunk insertion to MariaDB (200 records per chunk)
            foreach (array_chunk($insertBatch, 200) as $chunk) {
                Participant::insert($chunk);
            }

            // Tamper-proof audit logging
            AuditLog::record(
                'ROSTER_IMPORT_MAPPED',
                'Roster',
                (string) $roster->id,
                "Imported {$createdCount} participants (v{$version}) via interactive column mapping for '{$activity->title}'."
            );

            UserNotification::notify(
                $activity->user_id,
                'Roster imported',
                "{$createdCount} participants loaded into \"{$activity->title}\" (v{$version}).",
                'roster',
                route('rosters.index', ['activity_id' => $activity->id]),
                'normal'
            );
        });

        return redirect()->route('rosters.index', ['activity_id' => $activity->id])
            ->with('success', "Dataset imported successfully! Registered {$createdCount} students (v" . ($activity->rosters()->max('version')) . ").");
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendra_roster_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Full Name', 'Matric Number', 'IC Last 4 Digits', 'Email Address', 'Program Code']);
            fputcsv($file, ['MUHAMMAD AMIRUL BIN HAKIM', 'B032110042', '5123', 'amirul@student.utem.edu.my', 'BITS']);
            fputcsv($file, ['NURUL AINA BINTI ZAINAL', 'B032110189', '6048', 'aina@student.utem.edu.my', 'BITD']);
            fputcsv($file, ['TAN KAH HENG', 'B032110255', '1182', 'tan@student.utem.edu.my', 'BITM']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
