@extends('layouts.app', ['title' => 'Campus Activities Oversight'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                Campus Activities Oversight
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Centralized monitoring of all student activities, cohorts, and sessions across faculties
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-full bg-[#FAF7F2] text-[#1C1C1E] text-xs font-bold border border-[#F0EAE1] flex items-center gap-1.5">
                <i class="bi bi-collection text-[#8E8E93]"></i> Total: {{ $activities->total() }} Activities
            </span>
        </div>
    </div>

    <!-- Activities Table (FigCraft D11) -->
    <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-sm overflow-hidden p-6 space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#FAF7F2] text-[#8E8E93] font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4 rounded-l-xl">ACTIVITY & CODE</th>
                        <th class="py-3 px-4">FACULTY</th>
                        <th class="py-3 px-4">STAFF ORGANIZER</th>
                        <th class="py-3 px-4">SESSIONS</th>
                        <th class="py-3 px-4">ROSTER CAPACITY</th>
                        <th class="py-3 px-4 rounded-r-xl text-right">STATUS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#FAF7F2]">
                    @forelse($activities as $act)
                        <tr class="hover:bg-[#FAF7F2]/60 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-[#1C1C1E]">
                                {{ $act->title }}
                                <div class="text-[11px] font-mono text-[#8E8E93] font-normal">{{ $act->slug }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#E8EAFA] text-[#4C5270]">
                                    {{ $act->faculty }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-[#1C1C1E]">
                                <span class="font-semibold">{{ $act->user->name ?? 'System' }}</span>
                                <div class="text-[10px] text-[#8E8E93]">{{ $act->user->email ?? '—' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-[#1C1C1E]">
                                {{ $act->sessions->count() }} Sessions
                            </td>
                            <td class="py-3.5 px-4 text-[#1C1C1E]">
                                <span class="font-bold">{{ $act->activeRoster?->total_records ?? 0 }}</span>
                                <span class="text-[11px] text-[#8E8E93]">(v{{ $act->activeRoster?->version ?? 1 }})</span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#EBF5EC] text-[#2E7D32]">
                                    ● {{ ucfirst($act->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-[#8E8E93]">
                                No activities registered across campus.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($activities->hasPages())
            <div class="pt-4 border-t border-[#F0EAE1]">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
