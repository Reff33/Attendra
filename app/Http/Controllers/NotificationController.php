<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $filter = $request->string('filter')->toString() ?: 'all';
        $search = trim($request->string('search')->toString());

        $base = UserNotification::where('user_id', $user->id);

        $summary = [
            'all' => (clone $base)->count(),
            'unread' => (clone $base)->whereNull('read_at')->count(),
            'important' => (clone $base)->whereIn('priority', ['high', 'urgent'])->count(),
        ];

        $query = clone $base;

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'important') {
            $query->whereIn('priority', ['high', 'urgent']);
        } elseif (in_array($filter, ['roster', 'session', 'record', 'activity', 'system'], true)) {
            $query->where('type', $filter);
        }

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('message', 'like', "%{$search}%"));
        }

        $notifications = $query->latest()->paginate(10)->withQueryString();

        return view('staff.notifications.index', compact('notifications', 'summary', 'filter', 'search'));
    }

    /**
     * Open one notification, marking it read.
     */
    public function show(UserNotification $notification)
    {
        $this->authorizeOwnership($notification);

        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        if ($notification->url) {
            return redirect($notification->url);
        }

        return view('staff.notifications.show', compact('notification'));
    }

    public function markRead(UserNotification $notification)
    {
        $this->authorizeOwnership($notification);
        $notification->update(['read_at' => now()]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead()
    {
        UserNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(UserNotification $notification)
    {
        $this->authorizeOwnership($notification);
        $notification->delete();

        return back()->with('success', 'Notification removed.');
    }

    private function authorizeOwnership(UserNotification $notification): void
    {
        if ($notification->user_id !== Auth::id()) {
            abort(404);
        }
    }
}
