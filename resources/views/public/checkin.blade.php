<!DOCTYPE html>
<html lang="en" class="h-full bg-[#FAF7F2]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>{{ $session->title }} — Student Attendance Check-In</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-between py-6 px-4 sm:px-6 bg-[#FAF7F2]">
    <div class="max-w-md mx-auto w-full space-y-5">
        <!-- Top University & Event Header -->
        <div class="text-center space-y-1">
            <span class="text-2xl font-bold tracking-tight text-[#1C1C1E] inline-flex items-center gap-1">
                Attendra<span class="text-[#F8A926]">.</span>
            </span>
            <div class="flex items-center justify-center gap-1.5 text-xs text-[#8E8E93]">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Universiti Teknikal Malaysia Melaka</span>
            </div>
        </div>

        <!-- Session Overview Card (Warm Organic Ivory) -->
        <div class="bg-white rounded-[28px] p-6 border border-[#F0EAE1] shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-3">
            <div class="flex items-center justify-between">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#EBF5EC] text-[#2E7D32] flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#2E7D32] animate-pulse"></span>
                    SESSION ACTIVE
                </span>
                <span class="text-[11px] font-semibold text-[#8E8E93]">{{ $session->activity->faculty }}</span>
            </div>

            <div>
                <h1 class="text-base font-bold text-[#1C1C1E]">{{ $session->title }}</h1>
                <p class="text-xs text-[#8E8E93] mt-0.5 flex items-center gap-1">
                    <i class="bi bi-geo-alt"></i> {{ $session->venue }}
                </p>
            </div>

            <!-- Verification Mode Tags -->
            <div class="pt-2 border-t border-[#F0EAE1] flex items-center gap-2 text-[10px]">
                <span class="text-[#8E8E93]">Anti-Proxy:</span>
                @if(in_array($session->verification_mode, ['ic_last_4', 'ic_and_gps']))
                    <span class="px-2 py-0.5 rounded-md bg-[#FAF7F2] border border-[#F0EAE1] font-bold text-[#1C1C1E] flex items-center gap-1">
                        <i class="bi bi-person-vcard text-[#8E8E93]"></i> 4-Digit IC
                    </span>
                @endif
                @if(in_array($session->verification_mode, ['gps', 'ic_and_gps']))
                    <span class="px-2 py-0.5 rounded-md bg-[#FAF7F2] border border-[#F0EAE1] font-bold text-[#1C1C1E] flex items-center gap-1">
                        <i class="bi bi-geo-alt-fill text-[#2E7D32]"></i> GPS Radar ({{ $session->geofence_radius_meters }}m)
                    </span>
                @endif
            </div>
        </div>

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-[#FDE8E1] border border-[#E07A5F]/20 text-[#1C1C1E] text-xs flex items-center gap-2">
                <i class="bi bi-exclamation-circle-fill text-[#E07A5F]"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Public Check-In Form with Roster Autocomplete (Alpine.js) -->
        <div x-data="{
                search: '',
                results: [],
                selectedStudent: null,
                ic4: '',
                gpsReady: true,
                async fetchStudents() {
                    if (this.search.length < 2) { this.results = []; return; }
                    try {
                        const res = await fetch(`{{ route('public.roster-search', $session->access_token) }}?q=${encodeURIComponent(this.search)}`);
                        const data = await res.json();
                        this.results = data.participants || [];
                    } catch (e) {
                        this.results = [];
                    }
                },
                select(item) {
                    this.selectedStudent = item;
                    this.search = item.student_name || item.name;
                    this.results = [];
                },
                gpsStatus: 'idle',
                studentLat: null,
                studentLng: null,
                gpsError: '',
                init() {
                    @if(in_array($session->verification_mode, ['gps', 'ic_and_gps']))
                        this.getGPSLocation();
                    @endif
                },
                getGPSLocation() {
                    this.gpsStatus = 'detecting';
                    if (!navigator.geolocation) {
                        this.gpsStatus = 'failed';
                        this.gpsError = 'Device does not support GPS';
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.studentLat = Number(pos.coords.latitude.toFixed(7));
                            this.studentLng = Number(pos.coords.longitude.toFixed(7));
                            this.gpsStatus = 'success';
                        },
                        (err) => {
                            // No fallback: a spoofed venue coordinate would defeat the geofence entirely.
                            this.gpsStatus = 'failed';
                            this.gpsError = err.code === 1
                                ? 'Location permission denied. Enable it and retry.'
                                : 'Could not read your location. Move to an open area and retry.';
                            this.studentLat = null;
                            this.studentLng = null;
                        },
                        { enableHighAccuracy: true, timeout: 8000 }
                    );
                }
             }"
             class="bg-white rounded-[28px] p-6 border border-[#F0EAE1] shadow-sm space-y-5">

            <form method="POST" action="{{ route('public.checkin.submit', $session->access_token) }}" class="space-y-4">
                @csrf

                <!-- Name / Matric Autocomplete Input -->
                <div class="relative">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                        Search Your Name or Matric No
                    </label>
                    <div class="relative">
                        <input type="text" x-model="search" @input.debounce.300ms="fetchStudents()" required
                               placeholder="Type your name or B03..."
                               class="w-full pl-10 pr-4 py-3 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                        <i class="bi bi-search absolute left-3.5 top-3 text-[#8E8E93]"></i>
                    </div>

                    <!-- Autocomplete Dropdown List -->
                    <div x-show="results.length > 0" x-cloak
                         class="absolute z-20 left-0 right-0 mt-1 bg-white rounded-2xl border border-[#F0EAE1] shadow-xl max-h-48 overflow-y-auto divide-y divide-[#FAF7F2]">
                        <template x-for="item in results" :key="item.id">
                            <button type="button" @click="select(item)"
                                    class="w-full text-left p-3 hover:bg-[#FAF7F2] transition-colors flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-[#1C1C1E]" x-text="item.student_name || item.name"></div>
                                    <div class="text-[10px] font-mono text-[#8E8E93]" x-text="item.matric_no"></div>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-[#FAF7F2] border border-[#F0EAE1] font-semibold text-[#1C1C1E]" x-text="item.program_code || item.program"></span>
                            </button>
                        </template>
                    </div>

                    <input type="hidden" name="matric_no" :value="selectedStudent ? selectedStudent.matric_no : search">
                </div>

                <!-- Selected Student Preview Confirmation Pill -->
                <div x-show="selectedStudent" x-cloak
                     class="p-3 bg-[#EBF5EC] rounded-2xl border border-[#2E7D32]/20 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-[#2E7D32]" x-text="selectedStudent ? (selectedStudent.student_name || selectedStudent.name) : ''"></div>
                        <div class="text-[11px] font-mono text-[#2E7D32]/80" x-text="selectedStudent ? selectedStudent.matric_no : ''"></div>
                    </div>
                    <span class="text-[10px] font-bold text-[#2E7D32] bg-white px-2 py-0.5 rounded-full shadow-xs flex items-center gap-1">
                        <i class="bi bi-check-circle-fill text-[#2E7D32]"></i> Roster Matched
                    </span>
                </div>

                <!-- IC Last 4 Digits Verification (Conditional) -->
                @if(in_array($session->verification_mode, ['ic_last_4', 'ic_and_gps']))
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold uppercase tracking-wider text-[#1C1C1E]">
                                IC Number (Last 4 Digits)
                            </label>
                            <span class="text-[11px] text-[#8E8E93]">e.g. 5123</span>
                        </div>
                        <input type="password" name="ic_last_4" maxlength="4" required
                               placeholder="••••"
                               class="w-full px-4 py-3 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-center font-mono text-base tracking-widest text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                    </div>
                @endif

                <!-- GPS Radar Indicator (Conditional) -->
                @if(in_array($session->verification_mode, ['gps', 'ic_and_gps']))
                    <div class="p-3.5 rounded-2xl border transition-all"
                         :class="gpsStatus === 'success' ? 'bg-[#EBF5EC] border-[#2E7D32]/30' : (gpsStatus === 'failed' ? 'bg-[#FDE8E1] border-[#E07A5F]/30' : 'bg-[#FAF7F2] border-[#F0EAE1]')">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <i class="bi bi-geo-alt-fill text-lg"
                                   :class="gpsStatus === 'success' ? 'text-[#2E7D32]' : (gpsStatus === 'failed' ? 'text-[#E07A5F]' : 'text-[#F8A926] animate-pulse')"></i>
                                <div>
                                    <div class="text-xs font-bold text-[#1C1C1E]">
                                        GPS Radar ({{ $session->venue }})
                                    </div>
                                    <template x-if="gpsStatus === 'detecting'">
                                        <div class="text-[10px] text-[#8E8E93] flex items-center gap-1">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#F8A926] animate-ping"></span>
                                            <span>Verifying device GPS location within geofence radius...</span>
                                        </div>
                                    </template>
                                    <template x-if="gpsStatus === 'success'">
                                        <div class="text-[10px] text-[#2E7D32] font-semibold flex items-center gap-1">
                                            <i class="bi bi-check-circle-fill"></i>
                                            <span>Valid location detected (&plusmn;{{ $session->geofence_radius_meters ?? 100 }}m attendance zone)</span>
                                        </div>
                                    </template>
                                    <template x-if="gpsStatus === 'failed'">
                                        <div class="text-[10px] text-[#E07A5F] flex items-center gap-1">
                                            <span x-text="gpsError"></span>
                                            <button type="button" @click="getGPSLocation()" class="underline font-bold ml-1">Retry GPS</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full"
                                  :class="gpsStatus === 'success' ? 'bg-[#2E7D32] text-white' : (gpsStatus === 'failed' ? 'bg-[#E07A5F] text-white' : 'bg-[#F8A926] text-[#1C1C1E]')">
                                <span x-text="gpsStatus === 'success' ? 'PASSED' : (gpsStatus === 'failed' ? 'FAILED' : 'DETECTING')"></span>
                            </span>
                        </div>
                    </div>
                    <input type="hidden" name="latitude" :value="studentLat ?? ''">
                    <input type="hidden" name="longitude" :value="studentLng ?? ''">
                @endif

                <!-- Submit Button -->
                <button type="submit"
                        @if(in_array($session->verification_mode, ['gps', 'ic_and_gps']))
                        :disabled="gpsStatus === 'failed'"
                        :class="gpsStatus === 'failed' ? 'opacity-50 cursor-not-allowed' : ''"
                        @endif
                        class="w-full py-3.5 px-4 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-2">
                    <i class="bi bi-qr-code"></i> Confirm & Check In Attendance
                </button>
            </form>
        </div>

        <!-- Student Help Note -->
        <p class="text-center text-[11px] text-[#8E8E93] leading-relaxed">
            Trouble checking in? Approach the event organizer table for a manual walk-in registration.
        </p>
    </div>
</body>
</html>
