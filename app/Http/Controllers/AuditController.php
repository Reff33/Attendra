<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized. Audit Trail & Compliance Log is restricted to Administrators.');
        }

        $query = AuditLog::with('user')->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action_type', $request->action);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('details', 'like', "%{$search}%")
                  ->orWhere('actor_name', 'like', "%{$search}%")
                  ->orWhere('actor_staff_id', 'like', "%{$search}%")
                  ->orWhere('action_type', 'like', "%{$search}%")
                  ->orWhere('target_entity', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        // Get distinct action types for filter dropdown
        $actionTypes = AuditLog::select('action_type')->distinct()->orderBy('action_type')->pluck('action_type');

        return view('admin.audit.index', compact('logs', 'actionTypes'));
    }

    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized. Audit Trail & Compliance Log is restricted to Administrators.');
        }

        $query = AuditLog::with('user')->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action_type', $request->action);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('details', 'like', "%{$search}%")
                  ->orWhere('actor_name', 'like', "%{$search}%")
                  ->orWhere('actor_staff_id', 'like', "%{$search}%")
                  ->orWhere('action_type', 'like', "%{$search}%")
                  ->orWhere('target_entity', 'like', "%{$search}%");
            });
        }

        $filename = 'Attendra_Audit_Compliance_Trail_' . now()->format('Ymd_His') . '.csv';

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Log ID',
                'Timestamp',
                'Action Type',
                'Actor Name',
                'Staff ID',
                'IP Address',
                'Target Entity',
                'Target ID',
                'Audit Details',
                'Cryptographic Proof (SHA-256)',
            ]);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->action_type,
                        $log->actor_name ?? $log->user?->name ?? 'System',
                        $log->actor_staff_id ?? $log->user?->staff_id ?? 'N/A',
                        $log->ip_address,
                        $log->target_entity,
                        $log->target_id,
                        $log->details,
                        $log->sha256_hash,
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
