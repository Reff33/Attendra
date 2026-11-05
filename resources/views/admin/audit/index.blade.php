@extends('layouts.app', ['title' => 'Audit Trail & Compliance Log'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                System Audit Trail & Compliance Log
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Administrator cryptographic SHA-256 ledger tracking all roster imports, session changes, and account actions
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.audit.export', request()->query()) }}"
               class="px-4 py-2 rounded-xl bg-white border border-[#F0EAE1] hover:bg-[#FAF7F2] text-xs font-bold text-[#1C1C1E] shadow-2xs flex items-center gap-1.5 transition-colors">
                <i class="bi bi-file-earmark-arrow-down-fill text-[#2E7D32]"></i> Export Audit CSV
            </a>
            <span class="px-3 py-2 rounded-xl bg-[#EBF5EC] text-[#2E7D32] text-xs font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#2E7D32]"></span> Ledger Verified
            </span>
        </div>
    </div>

    <!-- Filters & Search Toolbar (Warm Organic Card) -->
    <div class="bg-white rounded-2xl p-4 border border-[#F0EAE1] shadow-2xs">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search logs by keyword, staff name, staff ID, or entity..."
                       class="w-full pl-9 pr-4 py-2 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                <i class="bi bi-search absolute left-3 top-2.5 text-[#8E8E93]"></i>
            </div>

            <div class="w-full sm:w-auto flex items-center gap-2">
                <select name="action" class="w-full sm:w-48 px-3 py-2 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs font-semibold text-[#1C1C1E] focus:outline-none focus:border-[#F8A926]">
                    <option value="">All Action Types</option>
                    @foreach($actionTypes as $type)
                        <option value="{{ $type }}" {{ request('action') === $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>

                <button type="submit"
                        class="px-4 py-2 rounded-xl bg-[#1C1C1E] hover:bg-black text-white text-xs font-bold transition-all shrink-0">
                    Filter
                </button>

                @if(request('search') || request('action'))
                    <a href="{{ route('admin.audit.index') }}"
                       class="px-3 py-2 rounded-xl bg-[#FAF7F2] hover:bg-[#F0EAE1] text-[#8E8E93] hover:text-[#1C1C1E] text-xs font-bold transition-all shrink-0">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Audit Table Card (FigCraft D8) -->
    <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-sm overflow-hidden p-6 space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#FAF7F2] text-[#8E8E93] font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4 rounded-l-xl">TIMESTAMP</th>
                        <th class="py-3 px-4">ACTION TYPE</th>
                        <th class="py-3 px-4">ACTOR / STAFF</th>
                        <th class="py-3 px-4">AUDIT DETAILS</th>
                        <th class="py-3 px-4 rounded-r-xl font-mono text-right">SHA-256 PROOF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#FAF7F2]">
                    @forelse($logs as $log)
                        <tr class="hover:bg-[#FAF7F2]/60 transition-colors">
                            <td class="py-3.5 px-4 font-mono text-[11px] text-[#8E8E93] whitespace-nowrap">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-3.5 px-4 font-bold">
                                @if(str_contains($log->action_type, 'OVERRIDE') || str_contains($log->action_type, 'SUSPEND'))
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#FDE8E1] text-[#E07A5F]">
                                        {{ $log->action_type }}
                                    </span>
                                @elseif(str_contains($log->action_type, 'CREATE') || str_contains($log->action_type, 'OPEN') || str_contains($log->action_type, 'ACTIVATE'))
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#EBF5EC] text-[#2E7D32]">
                                        {{ $log->action_type }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#FAF7F2] text-[#1C1C1E] border border-[#F0EAE1]">
                                        {{ $log->action_type }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-medium text-[#1C1C1E]">
                                {{ $log->actor_name ?? $log->user?->name ?? 'System' }}
                                <div class="text-[10px] text-[#8E8E93] font-mono">{{ $log->actor_staff_id ?? $log->user?->staff_id ?? 'N/A' }} &bull; {{ $log->ip_address ?? '127.0.0.1' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-[#1C1C1E] max-w-md">
                                {{ $log->details }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-[11px] text-[#8E8E93] truncate max-w-[120px]" title="{{ $log->sha256_hash }}">
                                {{ substr($log->sha256_hash, 0, 16) }}...
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-8 text-[#8E8E93]">
                                No audit records logged.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="pt-4 border-t border-[#F0EAE1]">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
