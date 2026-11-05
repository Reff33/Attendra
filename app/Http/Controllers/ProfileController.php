<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EventSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        $activitiesCount = $user->activities()->count();
        $sessionsCount = EventSession::whereHas('activity', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $totalAttendance = EventSession::whereHas('activity', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->withCount('attendanceRecords')->get()->sum('attendance_records_count');

        $recentLogs = AuditLog::where('user_id', $user->id)
            ->latest('created_at')
            ->take(5)
            ->get();

        // Active Devices Sessions (Max 3 Devices Concept like UIDM)
        $currentSessionId = session()->getId();
        $deviceSessions = collect();

        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            $deviceSessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderBy('last_activity', 'desc')
                ->get()
                ->map(function ($s) use ($currentSessionId) {
                    $agent = $s->user_agent ?? 'Unknown Browser';
                    $isCurrent = ($s->id === $currentSessionId);

                    // Device type & icon detection
                    $deviceType = 'Desktop / Laptop';
                    $icon = 'bi-laptop';
                    if (stripos($agent, 'Mobile') !== false || stripos($agent, 'Android') !== false || stripos($agent, 'iPhone') !== false) {
                        $deviceType = 'Smartphone';
                        $icon = 'bi-phone';
                    } elseif (stripos($agent, 'iPad') !== false || stripos($agent, 'Tablet') !== false) {
                        $deviceType = 'Tablet';
                        $icon = 'bi-tablet';
                    }

                    // Browser detection
                    $browser = 'Web Browser';
                    if (stripos($agent, 'Edg') !== false) $browser = 'Microsoft Edge';
                    elseif (stripos($agent, 'Chrome') !== false) $browser = 'Google Chrome';
                    elseif (stripos($agent, 'Safari') !== false) $browser = 'Apple Safari';
                    elseif (stripos($agent, 'Firefox') !== false) $browser = 'Mozilla Firefox';

                    return (object) [
                        'id' => $s->id,
                        'ip_address' => $s->ip_address ?: '127.0.0.1',
                        'device_type' => $deviceType,
                        'browser' => $browser,
                        'icon' => $icon,
                        'last_active' => Carbon::createFromTimestamp($s->last_activity)->diffForHumans(),
                        'is_current' => $isCurrent,
                    ];
                });
        }

        return view('profile.edit', compact(
            'user',
            'activitiesCount',
            'sessionsCount',
            'totalAttendance',
            'recentLogs',
            'deviceSessions'
        ));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',
            'faculty' => 'nullable|string|max:100',
        ]);

        $user->update($validated);

        AuditLog::record(
            'PROFILE_UPDATE',
            'User',
            (string) $user->id,
            "User {$user->name} updated their profile particulars"
        );

        return back()->with('success', 'Profile information updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLog::record(
            'PASSWORD_CHANGE',
            'User',
            (string) $user->id,
            "User {$user->name} changed their account credentials"
        );

        return back()->with('success', 'Password updated successfully.');
    }

    public function logoutOtherDevices()
    {
        $user = Auth::user();
        $currentSessionId = session()->getId();

        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();

            AuditLog::record(
                'REVOKE_ALL_OTHER_SESSIONS',
                'User',
                (string) $user->id,
                "User {$user->name} revoked all other active device sessions"
            );
        }

        return back()->with('success', 'All other active device sessions have been signed out successfully.');
    }

    public function logoutSingleDevice(string $sessionId)
    {
        $user = Auth::user();
        $currentSessionId = session()->getId();

        if ($sessionId === $currentSessionId) {
            return back()->with('error', 'Cannot sign out the current active session from here. Please use the Sign Out button.');
        }

        if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', $sessionId)
                ->delete();

            AuditLog::record(
                'REVOKE_DEVICE_SESSION',
                'User',
                (string) $user->id,
                "User {$user->name} revoked device session ID {$sessionId}"
            );
        }

        return back()->with('success', 'The device session has been signed out successfully.');
    }
}
