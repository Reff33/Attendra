@extends('layouts.app', ['title' => 'Organizer Dashboard'])

@section('content')
<div class="space-y-6" x-data="organizerLiveDashboard()">
    <!-- Header Greeting & Create CTA -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-[28px] font-extrabold tracking-tight text-[#1C1C1E]">
                Welcome Back, {{ $user->name }}
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                FTMK Staff Organizer • {{ $liveSession ? $liveSession->venue : 'Dewan Al-Jazari' }} Live Session
            </p>
        </div>
        <div class="flex items-center gap-3">
            <div class="px-4 py-2 rounded-full bg-white border border-[#F0EAE1] text-xs font-semibold text-[#1C1C1E] shadow-2xs flex items-center gap-2 cursor-pointer hover:bg-[#FAF7F2] transition-colors">
                <i class="bi bi-calendar3 text-[#8E8E93]"></i>
                <span>{{ now()->format('d M, Y') }} - Today</span>
                <i class="bi bi-chevron-down text-[10px] text-[#8E8E93]"></i>
            </div>
            <a href="{{ route('sessions.create') }}"
               class="px-5 py-2.5 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                <i class="bi bi-plus-lg font-bold"></i> + Create New Session
            </a>
        </div>
    </div>

    <!-- 3-Column Top KPI Grid (Exact match FigCraft D3: equal 366px total height) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">

        <!-- Column 1 (Left): Attendance Live Pass Card (216px) + Daily Turnout Rate Card (136px) -->
        <div class="lg:col-span-3 flex flex-col justify-between gap-3.5">
            <!-- Attendance Pass Light Butter/Gold Card (216px) -->
            <div class="min-h-[216px] lg:h-[216px] bg-[#FEEFD0] border border-[#F8A926]/30 rounded-[24px] p-6 text-[#1C1C1E] shadow-2xs flex flex-col justify-between relative overflow-hidden">
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold tracking-wider uppercase text-[#7A521E]">
                            ATTENDRA PASS &bull; )))
                        </span>
                        <span class="w-2 h-2 rounded-full bg-[#2E7D32] animate-ping" title="Live Broadcast Active"></span>
                    </div>
                    <p class="text-[11px] font-semibold text-[#7A521E]/90">
                        Active Live Session &bull; {{ $liveSession ? $liveSession->venue : 'Dewan Al-Jazari' }}
                    </p>

                    <div class="pt-2">
                        <div class="text-[30px] font-black tracking-tight leading-none text-[#1C1C1E]">
                            <span x-text="checkedInCount">{{ $checkedInCount }}</span> / <span x-text="totalRegistered">{{ $totalRegistered }}</span> Checked In
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-extrabold text-[#1C1C1E]">
                        {{ $liveSession ? $liveSession->title : 'Majlis Amanat Dekan FTMK' }}
                    </h4>
                    <p class="text-[11px] text-[#7A521E]/80 mt-0.5">
                        Closes at {{ $liveSession?->ends_at?->format('h:i A') ?? '11:30 AM' }} (Live Stream Active)
                    </p>
                </div>
            </div>

            <!-- Daily Turnout Rate White Card (136px) -->
            <div class="min-h-[136px] lg:h-[136px] bg-white rounded-[24px] p-5 sm:p-6 border border-[#F0EAE1] shadow-2xs flex flex-col justify-between">
                <div>
                    <span class="text-[11px] font-medium text-[#8E8E93]">Daily Turnout Rate</span>
                    <div class="text-[26px] font-extrabold text-[#1C1C1E] tracking-tight leading-tight mt-0.5">
                        <span x-text="rate + '%'">{{ $rate }}%</span> Verified
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-1 flex-wrap text-[11px]">
                    <span class="px-2.5 py-0.5 rounded-full font-bold bg-[#EBF5EC] text-[#2E7D32]">
                        +3.8% vs last sem
                    </span>
                    <span class="text-[#8E8E93] text-[11px]">Geofence GPS + 4-Digit IC Active</span>
                </div>
            </div>
        </div>

        <!-- Column 2 (Center): Attendance Velocity Bar Chart Card (366px, FigCraft D3) -->
        <div class="lg:col-span-5 min-h-[366px] lg:h-[366px] bg-white rounded-[24px] p-6 border border-[#F0EAE1] shadow-2xs flex flex-col justify-between">
            <div class="flex items-center justify-between pb-1">
                <h3 class="text-sm font-bold text-[#1C1C1E]">Attendance Velocity</h3>
                <div class="flex items-center gap-2">
                    <div class="bg-[#FAF7F2] p-1 rounded-full border border-[#F0EAE1] flex items-center text-[10px] font-semibold">
                        <button class="px-3 py-0.5 rounded-full bg-white text-[#1C1C1E] shadow-2xs font-bold">This Session</button>
                        <button class="px-3 py-0.5 text-[#8E8E93] hover:text-[#1C1C1E]">Monthly</button>
                    </div>
                    <button class="text-[#8E8E93] hover:text-[#1C1C1E] text-xs p-1">
                        <i class="bi bi-arrow-up-right"></i>
                    </button>
                </div>
            </div>

            <!-- Velocity Chart Visual Container (230px) -->
            <div class="relative w-full pt-6 pb-1">
                <!-- 4 Horizontal Grid Lines (Matching FigCraft D3 lines 2623-2626) -->
                <div class="absolute inset-x-0 top-3 border-b border-[#FAF7F2]"></div>
                <div class="absolute inset-x-0 top-16 border-b border-[#FAF7F2]"></div>
                <div class="absolute inset-x-0 top-32 border-b border-[#FAF7F2]"></div>
                <div class="absolute inset-x-0 bottom-6 border-b border-[#F0EAE1]"></div>

                <!-- 6 Pillar Bars (Sitting on baseline y=470 with exact pixel heights) -->
                <div class="relative z-10 flex items-end justify-between px-3 sm:px-6 h-[200px]">
                    <!-- Bar 1 (08:30) - 70px -->
                    <div class="flex flex-col items-center">
                        <div class="w-[34px] sm:w-[38px] bg-[#E5F0E6] rounded-full transition-all" style="height: 70px;"></div>
                        <span class="text-[10px] sm:text-[11px] font-semibold text-[#8E8E93] mt-2">08:30</span>
                    </div>

                    <!-- Bar 2 (09:00) - 120px -->
                    <div class="flex flex-col items-center">
                        <div class="w-[34px] sm:w-[38px] bg-[#E5F0E6] rounded-full transition-all" style="height: 120px;"></div>
                        <span class="text-[10px] sm:text-[11px] font-semibold text-[#8E8E93] mt-2">09:00</span>
                    </div>

                    <!-- Bar 3 (09:30) Peak Amber - 190px -->
                    <div class="flex flex-col items-center relative">
                        <!-- Peak Tooltip Pill centered above bar -->
                        <div class="absolute -top-7 left-1/2 -translate-x-1/2 whitespace-nowrap">
                            <span class="px-2.5 py-0.5 rounded-full bg-[#1C1C1E] text-white text-[10px] font-bold shadow-xs">
                                +17.8% Peak
                            </span>
                        </div>
                        <div class="w-[34px] sm:w-[38px] bg-[#F8A926] rounded-full shadow-xs transition-all" style="height: 190px;"></div>
                        <span class="text-[10px] sm:text-[11px] font-bold text-[#1C1C1E] mt-2">09:30</span>
                    </div>

                    <!-- Bar 4 (10:00) - 140px -->
                    <div class="flex flex-col items-center">
                        <div class="w-[34px] sm:w-[38px] bg-[#E5F0E6] rounded-full transition-all" style="height: 140px;"></div>
                        <span class="text-[10px] sm:text-[11px] font-semibold text-[#8E8E93] mt-2">10:00</span>
                    </div>

                    <!-- Bar 5 (10:30) - 90px -->
                    <div class="flex flex-col items-center">
                        <div class="w-[34px] sm:w-[38px] bg-[#E5F0E6] rounded-full transition-all" style="height: 90px;"></div>
                        <span class="text-[10px] sm:text-[11px] font-semibold text-[#8E8E93] mt-2">10:30</span>
                    </div>

                    <!-- Bar 6 (11:00) - 45px -->
                    <div class="flex flex-col items-center">
                        <div class="w-[34px] sm:w-[38px] bg-[#E5F0E6] rounded-full transition-all" style="height: 45px;"></div>
                        <span class="text-[10px] sm:text-[11px] font-semibold text-[#8E8E93] mt-2">11:00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column 3 (Right): Total Roster Capacity (216px) + On-Duty Staff & Datasets (136px) -->
        <div class="lg:col-span-4 flex flex-col justify-between gap-3.5">
            <!-- Capacity Card (216px) -->
            <div class="min-h-[216px] lg:h-[216px] bg-white rounded-[24px] p-6 border border-[#F0EAE1] shadow-2xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93]">
                        TOTAL ROSTER CAPACITY
                    </span>
                    <a href="{{ route('rosters.index') }}" class="text-[#8E8E93] hover:text-[#1C1C1E]">
                        <i class="bi bi-arrow-up-right text-xs"></i>
                    </a>
                </div>

                <div>
                    <div class="text-[30px] font-black text-[#1C1C1E] tracking-tight leading-none">
                        1,248 Students
                    </div>
                    <p class="text-xs font-bold text-[#2E7D32] mt-1.5">
                        Across 6 Active Faculty Sessions
                    </p>
                </div>

                <!-- Two Rounded Action Pill Buttons: Staff Scanner (Dark) & Manual Walk-In (Ivory) -->
                <div class="grid grid-cols-2 gap-3 pt-1">
                    <a href="{{ route('quick-ops.scanner') }}"
                       class="py-2.5 px-3 rounded-full bg-[#1C1C1E] hover:bg-black text-white text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-2">
                        <i class="bi bi-camera-fill text-sm"></i>
                        <span>Staff Scanner</span>
                    </a>
                    <a href="{{ route('quick-ops.walk-in') }}"
                       class="py-2.5 px-3 rounded-full bg-[#FAF7F2] hover:bg-[#F0EAE1] text-[#1C1C1E] text-xs font-bold border border-[#EAE6DF] transition-all flex items-center justify-center gap-2">
                        <i class="bi bi-pencil-fill text-xs text-[#8E8E93]"></i>
                        <span>Manual Walk-In</span>
                    </a>
                </div>
            </div>

            <!-- Lower Stat Card: Streamed Fast-Excel & On-Duty Staff (136px) -->
            <div class="min-h-[136px] lg:h-[136px] bg-white rounded-[24px] p-5 border border-[#F0EAE1] shadow-2xs grid grid-cols-2 gap-4 items-center">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93]">STREAMED FAST-EXCEL</span>
                    <div class="text-base font-extrabold text-[#1C1C1E] mt-1">
                        8 Rosters Loaded
                    </div>
                    <p class="text-[11px] text-[#8E8E93] mt-0.5">
                        <span class="text-[#2E7D32] font-semibold">0 schema errors</span> &bull; 5,000 capacity
                    </p>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93]">ON-DUTY STAFF</span>
                    </div>
                    <div class="text-[11px] font-bold text-[#2E7D32] mt-1 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#2E7D32]"></span>
                        <span>3 Active On Site</span>
                    </div>
                    <p class="text-[11px] font-bold text-[#1C1C1E] mt-0.5">
                        [FR] [ST] [KA] <span class="text-[#8E8E93] font-medium">+2</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Attendance Stream Table Card (FigCraft D3 Exact Reproduction) -->
    <div class="bg-white rounded-[24px] border border-[#F0EAE1] shadow-2xs overflow-hidden p-6 sm:p-7 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-[#1C1C1E] tracking-tight">
                Recent Attendance Stream (Real-Time Live Feed)
            </h3>
            <a href="{{ route('matrix.index') }}" class="text-[#8E8E93] hover:text-[#1C1C1E] text-xs">
                <i class="bi bi-arrow-up-right"></i>
            </a>
        </div>

        <!-- Filter Sub-Tabs matching FigCraft D3 -->
        <div class="flex items-center gap-5 text-xs font-semibold overflow-x-auto pt-1 pb-1">
            <span class="text-[#1C1C1E] font-bold cursor-pointer">[ All Live Check-Ins (<span x-text="checkedInCount">{{ $checkedInCount }}</span>) ]</span>
            <span class="text-[#8E8E93] hover:text-[#1C1C1E] cursor-pointer">Live Polling Stream (4s)</span>
        </div>

        <!-- Datatable Table -->
        <div class="overflow-x-auto pt-1">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead>
                    <tr class="bg-[#FAF7F2] text-[#8E8E93] font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-4 rounded-l-xl">PARTICIPANT NAME & MATRIC</th>
                        <th class="py-3 px-4">FACULTY / SESSION</th>
                        <th class="py-3 px-4">TIME</th>
                        <th class="py-3 px-4">VERIFICATION METHOD</th>
                        <th class="py-3 px-4">STATUS</th>
                        <th class="py-3 px-4 rounded-r-xl text-right">TOKEN HASH</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#FAF7F2]">
                    <template x-for="r in records" :key="r.token_hash">
                        <tr class="hover:bg-[#FAF7F2]/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-[#1C1C1E]" x-text="r.student_name"></div>
                                <div class="text-[11px] font-mono text-[#8E8E93]" x-text="r.matric_no"></div>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-[#1C1C1E]" x-text="r.venue"></td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-[#1C1C1E]" x-text="r.checked_in_at"></td>
                            <td class="py-3.5 px-4 font-semibold" :class="r.is_walk_in ? 'text-[#E07A5F]' : 'text-[#2E7D32]'">
                                <span class="inline-flex items-center gap-1.5">
                                    <i :class="r.is_walk_in ? 'bi bi-pencil-fill text-xs text-[#E07A5F]' : 'bi bi-check-lg text-[#2E7D32]'"></i>
                                    <span x-text="r.verification_method"></span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1"
                                      :class="r.is_walk_in ? 'bg-[#FDE8E1] text-[#E07A5F]' : 'bg-[#EBF5EC] text-[#2E7D32]'">
                                    <span x-text="r.is_walk_in ? '● Walk-In Approved' : '● Present'"></span>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-[11px] text-[#8E8E93]" x-text="'#' + r.token_hash"></td>
                        </tr>
                    </template>
                    <tr x-show="records.length === 0">
                        <td colspan="6" class="py-8 text-center text-[#8E8E93]">
                            No attendance records for this session yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-t border-[#F0EAE1] flex items-center justify-between text-[11px] text-[#8E8E93]">
            <span>Showing latest <span x-text="records.length"></span> live records • Real-time stream active • UNIQUE(session_id, matric_no) enforced</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function organizerLiveDashboard() {
        return {
            checkedInCount: {{ (int) $checkedInCount }},
            totalRegistered: {{ (int) $totalRegistered }},
            rate: {{ (float) $rate }},
            records: @json($initialRecords),
            pollInterval: null,

            init() {
                @if($liveSession)
                    this.pollInterval = setInterval(() => {
                        this.fetchStats();
                    }, 4000);
                @endif
            },

            async fetchStats() {
                try {
                    const res = await fetch("{{ route('sessions.live-stats', $liveSession?->id ?? 0) }}");
                    if (!res.ok) return;
                    const data = await res.json();
                    this.checkedInCount = data.checked_in_count;
                    if (data.total_roster) this.totalRegistered = data.total_roster;
                    if (data.rate !== undefined) this.rate = data.rate;
                    if (data.recent && Array.isArray(data.recent)) {
                        this.records = data.recent;
                    }
                } catch (e) {
                    // silently handle fetch error on background poll
                }
            }
        };
    }
</script>
@endpush
