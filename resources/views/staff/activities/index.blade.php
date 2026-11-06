@extends('layouts.app', ['title' => 'Activities'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">Activities</h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Each activity holds one roster and its attendance sessions
            </p>
        </div>
        <a href="{{ route('activities.create') }}"
           class="px-4 py-2 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 self-start sm:self-auto">
            <i class="bi bi-plus-lg"></i> New Activity
        </a>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 rounded-xl bg-[#EBF5EC] border border-[#2E7D32]/20 text-xs font-semibold text-[#2E7D32]">
            {{ session('success') }}
        </div>
    @endif

    @if($activities->isEmpty())
        <div class="bg-white rounded-[28px] p-10 border border-[#F0EAE1] shadow-sm text-center space-y-3">
            <p class="text-sm font-bold text-[#1C1C1E]">No activities yet</p>
            <p class="text-xs text-[#8E8E93] max-w-sm mx-auto">
                Create an activity first, then upload its participant roster and add attendance sessions.
            </p>
            <a href="{{ route('activities.create') }}"
               class="inline-block px-4 py-2 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold transition-all">
                Create your first activity
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($activities as $act)
                <div class="bg-white rounded-[24px] p-5 border border-[#F0EAE1] shadow-sm space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1 min-w-0">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold
                                {{ $act->status === 'archived' ? 'bg-[#FAF7F2] text-[#8E8E93]' : 'bg-[#EBF5EC] text-[#2E7D32]' }}">
                                {{ ucfirst($act->status) }}
                            </span>
                            <h3 class="text-sm font-bold text-[#1C1C1E] truncate">{{ $act->title }}</h3>
                            <p class="text-[11px] text-[#8E8E93]">
                                {{ $act->faculty ?? '—' }}
                                <span class="mx-1">•</span>
                                {{ $act->sessions_count }} sessions
                                <span class="mx-1">•</span>
                                {{ $act->activeRoster?->total_records ?? 0 }} participants
                            </p>
                        </div>
                        <a href="{{ route('activities.edit', $act) }}"
                           class="px-3 py-1.5 rounded-xl bg-[#FAF7F2] hover:bg-[#F0EAE1] text-[11px] font-bold text-[#1C1C1E] border border-[#F0EAE1] transition-colors shrink-0">
                            Edit
                        </a>
                    </div>

                    <div class="pt-3 border-t border-[#F0EAE1] flex items-center justify-between text-[11px]">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('rosters.index', ['activity_id' => $act->id]) }}" class="font-bold text-[#F8A926] hover:underline">
                                Roster
                            </a>
                            <a href="{{ route('matrix.index', ['activity_id' => $act->id]) }}" class="font-bold text-[#F8A926] hover:underline">
                                Reports
                            </a>
                        </div>
                        <form method="POST" action="{{ route('activities.toggle', $act) }}">
                            @csrf
                            <button type="submit" class="font-semibold text-[#8E8E93] hover:text-[#1C1C1E]">
                                {{ $act->status === 'archived' ? 'Restore' : 'Archive' }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        @if($activities->hasPages())
            <div>{{ $activities->links() }}</div>
        @endif
    @endif
</div>
@endsection
