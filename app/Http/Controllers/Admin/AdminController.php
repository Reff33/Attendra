<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalStaff = User::where('role', 'staff')->count();
        $totalActivities = Activity::count();
        $totalSessions = EventSession::count();
        $totalCheckIns = AttendanceRecord::count();

        $recentActivities = Activity::with(['user', 'sessions'])->latest()->take(5)->get();
        $recentAudits = AuditLog::with('user')->latest()->take(6)->get();

        return view('admin.dashboard', compact(
            'totalStaff',
            'totalActivities',
            'totalSessions',
            'totalCheckIns',
            'recentActivities',
            'recentAudits'
        ));
    }

    public function staffIndex()
    {
        $staffMembers = User::where('role', 'staff')->withCount('activities')->latest()->paginate(15);
        return view('admin.staff.index', compact('staffMembers'));
    }

    public function storeStaff(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'staff_id' => 'required|string|unique:users,staff_id',
            'faculty' => 'required|string|max:100',
            'password' => 'required|string|min:6',
        ]);

        $staff = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'staff_id' => $request->staff_id,
            'faculty' => $request->faculty,
            'role' => 'staff',
            'status' => 'active',
            'password' => Hash::make($request->password),
        ]);

        AuditLog::record(
            'ADMIN_ADD_STAFF',
            'User',
            (string) $staff->id,
            "Admin created new staff account for {$staff->name} ({$staff->staff_id}, {$staff->faculty})"
        );

        return back()->with('success', "New staff member {$staff->name} successfully provisioned.");
    }

    public function toggleStaffStatus(User $user)
    {
        $admin = Auth::user();
        if ($user->id === $admin->id) {
            return back()->with('error', 'You cannot change your own administrator status.');
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        // If suspended, revoke all active sessions immediately
        if ($newStatus === 'suspended' && config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        AuditLog::record(
            $newStatus === 'suspended' ? 'ADMIN_SUSPEND_STAFF' : 'ADMIN_ACTIVATE_STAFF',
            'User',
            (string) $user->id,
            "Admin {$admin->name} changed status of {$user->name} ({$user->staff_id}) to {$newStatus}"
        );

        return back()->with('success', "Staff account for {$user->name} is now {$newStatus}.");
    }

    public function resetStaffPassword(Request $request, User $user)
    {
        $admin = Auth::user();

        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Invalidate active sessions
        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        AuditLog::record(
            'ADMIN_RESET_STAFF_PASSWORD',
            'User',
            (string) $user->id,
            "Admin {$admin->name} reset password for staff {$user->name} ({$user->staff_id})"
        );

        return back()->with('success', "Password for {$user->name} has been reset successfully.");
    }

    public function activitiesIndex()
    {
        $activities = Activity::with(['user', 'sessions', 'activeRoster'])->latest()->paginate(15);
        return view('admin.activities.index', compact('activities'));
    }
}
