@extends('layouts.app', ['title' => 'Cohort Attendance Matrix'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                Cohort Attendance Matrix Grid
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Cross-tabulated multi-session attendance view with MariaDB duplicate verification
            </p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Activity Filter Selector -->
            <form method="GET" action="{{ route('matrix.index') }}">
                <select name="activity_id" onchange="this.form.submit()"
                        class="px-3.5 py-2 rounded-xl bg-white border border-[#F0EAE1] text-xs font-bold text-[#1C1C1E] shadow-sm focus:outline-none focus:border-[#F8A926]">
                    @foreach($activities as $act)
                        <option value="{{ $act->id }}" {{ $selectedActivity?->id === $act->id ? 'selected' : '' }}>
                            {{ $act->title }} ({{ $act->faculty }})
                        </option>
                    @endforeach
                </select>
            </form>

            @if($selectedActivity)
                <a href="{{ route('matrix.export', ['activity_id' => $selectedActivity->id]) }}"
                   class="px-4 py-2 rounded-xl bg-[#FAF7F2] hover:bg-[#F0EAE1] text-xs font-bold text-[#1C1C1E] border border-[#F0EAE1] shadow-sm transition-all flex items-center gap-1.5">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            @endif
        </div>
    </div>

    <!-- Matrix Grid Card (FigCraft D6) -->
    <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-sm p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#F0EAE1]">
            <div>
                <h3 class="text-sm font-bold text-[#1C1C1E]">
                    {{ $selectedActivity?->title ?? 'Select Activity' }}
                </h3>
                <p class="text-xs text-[#8E8E93]">
                    {{ $participants->count() }} Cohort Students • {{ $sessions->count() }} Configured Event Sessions
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1.5 text-[#2E7D32] font-semibold">
                    <span class="w-2 h-2 rounded-full bg-[#2E7D32]"></span> Present
                </span>
                <span class="flex items-center gap-1.5 text-[#E07A5F] font-semibold">
                    <span class="w-2 h-2 rounded-full bg-[#E07A5F]"></span> Walk-In Override
                </span>
                <span class="flex items-center gap-1.5 text-[#8E8E93] font-semibold">
                    <span class="w-2 h-2 rounded-full bg-[#EAE6DF]"></span> Absent
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#FAF7F2] text-[#8E8E93] font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4 rounded-l-xl sticky left-0 bg-[#FAF7F2] z-10">STUDENT NAME & MATRIC</th>
                        <th class="py-3 px-3">PROGRAM</th>
                        @forelse($sessions as $sess)
                            <th class="py-3 px-4 text-center">
                                <div class="font-bold text-[#1C1C1E]">{{ $sess->title }}</div>
                                <div class="text-[10px] text-[#8E8E93] font-normal">{{ $sess->starts_at?->format('d M, h:i A') ?? 'Session' }}</div>
                            </th>
                        @empty
                            <th class="py-3 px-4 text-center">NO SESSIONS</th>
                        @endforelse
                        <th class="py-3 px-4 rounded-r-xl text-center">ATTENDANCE RATE</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#FAF7F2]">
                    @forelse($participants as $p)
                        @php
                            $attendedCount = 0;
                        @endphp
                        <tr class="hover:bg-[#FAF7F2]/60 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-[#1C1C1E] sticky left-0 bg-white hover:bg-inherit z-10 border-r border-[#FAF7F2]">
                                {{ $p->student_name }}
                                <div class="text-[11px] font-mono text-[#8E8E93] font-normal">{{ $p->matric_no }}</div>
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="px-2 py-0.5 rounded bg-[#FAF7F2] border border-[#F0EAE1] font-semibold text-[#1C1C1E]">
                                    {{ $p->program_code }}
                                </span>
                            </td>

                            <!-- Dynamic Sessions Matrix Cell -->
                            @foreach($sessions as $sess)
                                @php
                                    $rec = $attendanceMap[$p->matric_no][$sess->id] ?? null;
                                    if ($rec) {
                                        $attendedCount++;
                                    }
                                @endphp
                                <td class="py-3.5 px-4 text-center">
                                    @if($rec)
                                        @if($rec->is_walk_in)
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-[#FDE8E1] text-[#E07A5F] font-bold text-xs shadow-sm"
                                                  title="Walk-In Verified: {{ $rec->checked_in_at->format('h:i A') }}">
                                                W
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-[#EBF5EC] text-[#2E7D32] font-bold text-xs shadow-sm"
                                                  title="Checked In: {{ $rec->checked_in_at->format('h:i A') }}">
                                                ✓
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-[#FAF7F2] text-[#8E8E93] text-xs">
                                            —
                                        </span>
                                    @endif
                                </td>
                            @endforeach

                            <!-- Summary Rate -->
                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $ratePct = $sessions->count() > 0 ? round(($attendedCount / $sessions->count()) * 100) : 0;
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $ratePct >= 80 ? 'bg-[#EBF5EC] text-[#2E7D32]' : ($ratePct >= 50 ? 'bg-[#FEEFD0] text-[#7A521E]' : 'bg-[#FAF7F2] text-[#8E8E93]') }}">
                                    {{ $attendedCount }} / {{ $sessions->count() }} ({{ $ratePct }}%)
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $sessions->count() + 3 }}" class="text-center py-8 text-[#8E8E93]">
                                No participants enrolled in this activity roster.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
