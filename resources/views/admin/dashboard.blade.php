@extends('layouts.app', ['title' => 'System Oversight Console'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                System Oversight Console
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Full-campus oversight across all faculties, staff accounts, and live sessions
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-full bg-[#FEEFD0] text-[#7A521E] text-xs font-bold border border-[#F8A926]/40 flex items-center gap-1.5">
                <i class="bi bi-shield-lock-fill"></i> System Administrator Mode
            </span>
        </div>
    </div>

    <!-- 4 KPI Stats Grid (FigCraft D9) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-[24px] p-5 border border-[#F0EAE1] shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E8E93]">CAMPUS STAFF</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-[#1C1C1E]">{{ $totalStaff }}</span>
                <span class="text-xs font-semibold text-[#8E8E93]">Organizers</span>
            </div>
            <a href="{{ route('admin.staff.index') }}" class="mt-4 text-xs font-bold text-[#F8A926] hover:underline flex items-center gap-1">
                Directory ↗
            </a>
        </div>

        <div class="bg-white rounded-[24px] p-5 border border-[#F0EAE1] shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E8E93]">TOTAL ACTIVITIES</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-[#1C1C1E]">{{ $totalActivities }}</span>
                <span class="text-xs font-semibold text-[#8E8E93]">All Faculties</span>
            </div>
            <a href="{{ route('admin.activities.index') }}" class="mt-4 text-xs font-bold text-[#F8A926] hover:underline flex items-center gap-1">
                All Activities ↗
            </a>
        </div>

        <div class="bg-white rounded-[24px] p-5 border border-[#F0EAE1] shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E8E93]">EVENT SESSIONS</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-[#1C1C1E]">{{ $totalSessions }}</span>
                <span class="text-xs font-semibold text-[#2E7D32]">● Live / Scheduled</span>
            </div>
            <a href="{{ route('sessions.index') }}" class="mt-4 text-xs font-bold text-[#F8A926] hover:underline flex items-center gap-1">
                View Broadcasts ↗
            </a>
        </div>

        <div class="bg-white rounded-[24px] p-5 border border-[#F0EAE1] shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E8E93]">TOTAL VERIFIED PASSES</span>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-[#1C1C1E]">{{ $totalCheckIns }}</span>
                <span class="text-xs font-semibold text-[#2E7D32]">Zero Duplicates</span>
            </div>
            <a href="{{ route('admin.audit.index') }}" class="mt-4 text-xs font-bold text-[#F8A926] hover:underline flex items-center gap-1">
                Audit Trail ↗
            </a>
        </div>
    </div>

    <!-- 2 Column Overview Section: Left Recent Activities, Right System Audits -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: All Campus Activities -->
        <div class="lg:col-span-7 bg-white rounded-[28px] border border-[#F0EAE1] shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#F0EAE1]">
                <div>
                    <h3 class="text-sm font-bold text-[#1C1C1E]">Recent Campus Activities</h3>
                    <p class="text-xs text-[#8E8E93]">Global visibility across faculties</p>
                </div>
                <a href="{{ route('admin.activities.index') }}" class="text-xs font-bold text-[#F8A926] hover:underline">
                    View All ↗
                </a>
            </div>

            <div class="space-y-3">
                @foreach($recentActivities as $act)
                    <div class="p-4 rounded-2xl bg-[#FAF7F2] border border-[#F0EAE1] flex items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#E8EAFA] text-[#4C5270]">
                                    {{ $act->faculty }}
                                </span>
                                <span class="text-xs font-bold text-[#1C1C1E]">{{ $act->title }}</span>
                            </div>
                            <p class="text-[11px] text-[#8E8E93] mt-1">
                                Organizer: {{ $act->user->name }} • {{ $act->sessions->count() }} Sessions
                            </p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#EBF5EC] text-[#2E7D32]">
                            Active
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Right: Recent System Audit Hash Stream -->
        <div class="lg:col-span-5 bg-white rounded-[28px] border border-[#F0EAE1] shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#F0EAE1]">
                <div>
                    <h3 class="text-sm font-bold text-[#1C1C1E]">SHA-256 System Stream</h3>
                    <p class="text-xs text-[#8E8E93]">Tamper-proof activity logs</p>
                </div>
                <a href="{{ route('admin.audit.index') }}" class="text-xs font-bold text-[#F8A926] hover:underline">
                    Full Ledger ↗
                </a>
            </div>

            <div class="space-y-3">
                @foreach($recentAudits as $log)
                    <div class="p-3 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-[#1C1C1E]">{{ $log->action_type }}</span>
                            <span class="font-mono text-[10px] text-[#8E8E93]">{{ $log->created_at->format('H:i:s') }}</span>
                        </div>
                        <p class="text-[11px] text-[#8E8E93] line-clamp-1">{{ $log->details }}</p>
                        <div class="font-mono text-[9px] text-[#8E8E93] truncate pt-1 border-t border-[#F0EAE1]">
                            PROOF: {{ substr($log->hash_proof, 0, 24) }}...
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
