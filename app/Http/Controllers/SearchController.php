<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\EventSession;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Global search across the records a user can actually reach.
     *
     * ponytail: LIKE-based, no index/FULLTEXT. Fine for thousands of rows;
     * add a FULLTEXT index if participant counts reach the hundreds of thousands.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = Auth::user();
        $q = trim($request->string('q')->toString());

        if (mb_strlen($q) < 2) {
            return response()->json(['activities' => [], 'sessions' => [], 'participants' => []]);
        }

        $like = "%{$q}%";
        $isAdmin = $user->isAdmin();

        $activities = Activity::query()
            ->when(! $isAdmin, fn ($query) => $query->where('user_id', $user->id))
            ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('faculty', 'like', $like))
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'faculty']);

        $sessions = EventSession::query()
            ->with('activity:id,user_id,title')
            ->when(! $isAdmin, fn ($query) => $query->whereHas('activity', fn ($a) => $a->where('user_id', $user->id)))
            ->where(fn ($query) => $query->where('title', 'like', $like)->orWhere('venue', 'like', $like))
            ->latest()
            ->take(5)
            ->get(['id', 'activity_id', 'title', 'venue', 'status']);

        $participants = Participant::query()
            ->with('activity:id,user_id,title')
            ->when(! $isAdmin, fn ($query) => $query->whereHas('activity', fn ($a) => $a->where('user_id', $user->id)))
            ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('matric_no', 'like', $like))
            ->take(5)
            ->get(['id', 'activity_id', 'name', 'matric_no']);

        return response()->json([
            'activities' => $activities->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'subtitle' => $a->faculty,
                'url' => route('activities.edit', $a),
            ]),
            'sessions' => $sessions->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'subtitle' => trim(($s->venue ?? '') . ' • ' . ucfirst($s->status), ' •'),
                'url' => route('matrix.index', ['activity_id' => $s->activity_id]),
            ]),
            'participants' => $participants->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->name,
                'subtitle' => $p->matric_no . ' • ' . ($p->activity?->title ?? ''),
                'url' => route('rosters.index', ['activity_id' => $p->activity_id]),
            ]),
        ]);
    }
}
