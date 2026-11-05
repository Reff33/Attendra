<!DOCTYPE html>
<html lang="en" class="h-full bg-[#FAF7F2]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $session->title }} — Projector Broadcast Screen | Attendra</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #FAF7F2;
            color: #1C1C1E;
        }
    </style>
</head>
<body class="min-h-full flex flex-col bg-[#FAF7F2] p-6 lg:p-10 select-none"
      x-data="projectorStream({{ $checkedInCount }}, {{ $totalRoster > 0 ? $totalRoster : 450 }})">

    <!-- Top Bar Navigation & Controls -->
    <header class="flex items-center justify-between pb-6 border-b border-[#F0EAE1]">
        <div class="flex items-center gap-4">
            <a href="{{ route('sessions.index') }}"
               class="px-4 py-2 rounded-full bg-white border border-[#F0EAE1] hover:bg-[#FAF7F2] text-xs font-bold text-[#1C1C1E] shadow-2xs flex items-center gap-1.5 transition-colors">
                <i class="bi bi-arrow-left"></i> Back to Sessions
            </a>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[#2E7D32] animate-ping"></span>
                <span class="text-xs uppercase font-extrabold tracking-wider text-[#2E7D32]">
                    ● LIVE ATTENDANCE BROADCAST
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Fullscreen Button -->
            <button @click="toggleFullscreen()"
                    class="px-4 py-2 rounded-full bg-white border border-[#F0EAE1] hover:bg-[#FAF7F2] text-xs font-bold text-[#1C1C1E] shadow-2xs flex items-center gap-2 transition-colors">
                <i class="bi bi-arrows-fullscreen text-[#F8A926]"></i>
                <span x-text="isFullscreen ? 'Exit Fullscreen' : 'Projector Fullscreen'"></span>
            </button>
            <div class="px-4 py-2 rounded-full bg-[#FEEFD0] border border-[#F8A926]/40 text-xs font-bold text-[#7A521E]">
                <span>{{ $session->activity->faculty ?? 'FTMK' }}</span>
            </div>
        </div>
    </header>

    <!-- Main Projector Dual Bento Layout -->
    <main class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center mt-6">

        <!-- Left: Huge Scannable QR Code Canvas (7 Cols) -->
        <div class="lg:col-span-7 flex flex-col items-center justify-center">
            <div class="bg-white rounded-[36px] p-8 sm:p-10 border border-[#F0EAE1] shadow-xl flex flex-col items-center max-w-lg w-full text-center relative overflow-hidden">
                <!-- Top Marigold Pill -->
                <div class="px-4 py-1 rounded-full bg-[#F8A926] text-[#1C1C1E] text-xs font-extrabold tracking-wider uppercase mb-5 shadow-xs">
                    SCAN QR CODE TO ATTEND
                </div>

                <!-- Canvas QR Box -->
                <div class="p-6 bg-[#FAF7F2] rounded-[28px] border-2 border-dashed border-[#F8A926]/60 shadow-inner flex flex-col items-center justify-center">
                    <div id="qrcodeCanvas" class="p-4 bg-white rounded-2xl shadow-sm flex items-center justify-center"></div>
                </div>

                <!-- Verification Badges -->
                <div class="mt-6 flex items-center gap-3 flex-wrap justify-center">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#FAF7F2] border border-[#F0EAE1] text-[#1C1C1E] flex items-center gap-1.5">
                        <i class="bi bi-geo-alt-fill text-[#F8A926]"></i> {{ $session->venue }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#FAF7F2] border border-[#F0EAE1] text-[#1C1C1E] flex items-center gap-1.5">
                        @if($session->verification_mode === 'ic_and_gps')
                            <i class="bi bi-shield-check text-[#2E7D32]"></i> 4-Digit IC + Geofence ({{ $session->geofence_radius_meters }}m)
                        @elseif($session->verification_mode === 'gps')
                            <i class="bi bi-geo text-[#2E7D32]"></i> GPS Geofence ({{ $session->geofence_radius_meters }}m)
                        @else
                            <i class="bi bi-person-badge text-[#2E7D32]"></i> 4-Digit IC Verified
                        @endif
                    </span>
                </div>

                <!-- Short URL Fallback for Students without Camera Scanner -->
                <div class="mt-4 pt-4 border-t border-[#F0EAE1] w-full text-center">
                    <p class="text-[11px] text-[#8E8E93]">Or open the direct attendance link in your mobile browser:</p>
                    <p class="text-xs font-mono font-bold text-[#F8A926] mt-0.5 select-all">
                        {{ route('public.checkin', $session->access_token) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Right: Live Attendance Stats & Real-Time Stream (5 Cols) -->
        <div class="lg:col-span-5 flex flex-col justify-center space-y-5">
            <!-- Event Header Card -->
            <div>
                <span class="text-xs font-extrabold uppercase tracking-widest text-[#F8A926]">ACTIVE ATTENDANCE SESSION</span>
                <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-[#1C1C1E] mt-1 leading-tight">
                    {{ $session->title }}
                </h1>
                <p class="text-sm font-medium text-[#8E8E93] mt-2 flex items-center gap-2">
                    <i class="bi bi-clock-history"></i>
                    <span>Ends: {{ $session->ends_at?->format('h:i A') ?? '11:30 AM' }}</span>
                </p>
            </div>

            <!-- Big Counter Bento Card -->
            <div class="bg-white rounded-[28px] p-6 border border-[#F0EAE1] shadow-sm space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-[#8E8E93]">TOTAL STUDENTS VERIFIED</span>
                <div class="flex items-baseline gap-2">
                    <span class="text-5xl sm:text-6xl font-black text-[#1C1C1E] tracking-tight" x-text="checkedIn">
                        {{ $checkedInCount }}
                    </span>
                    <span class="text-2xl font-bold text-[#8E8E93]">
                        / <span x-text="totalRoster">{{ $totalRoster > 0 ? $totalRoster : 450 }}</span>
                    </span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-[#FAF7F2] rounded-full h-3.5 p-0.5 border border-[#F0EAE1] overflow-hidden">
                    <div class="bg-[#F8A926] h-full rounded-full transition-all duration-500"
                         :style="`width: ${percentage}%`"></div>
                </div>
                <div class="flex items-center justify-between text-xs font-bold">
                    <span class="text-[#2E7D32]" x-text="percentage + '% Completed'"></span>
                    <span class="text-[#8E8E93]" x-text="(totalRoster - checkedIn) + ' Students Remaining'"></span>
                </div>
            </div>

            <!-- Live Feed Card -->
            <div class="bg-white rounded-[28px] p-6 border border-[#F0EAE1] shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8E8E93]">Recent Live Intake Feed</span>
                    <span class="w-2 h-2 rounded-full bg-[#2E7D32] animate-ping"></span>
                </div>

                <!-- Live Stream Feed List -->
                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <template x-for="rec in recentRecords" :key="rec.token_hash">
                        <div class="p-3 bg-[#FAF7F2] rounded-xl border border-[#F0EAE1] flex items-center justify-between transition-all">
                            <div>
                                <div class="text-xs font-bold text-[#1C1C1E]" x-text="rec.student_name"></div>
                                <div class="text-[10px] font-mono text-[#8E8E93]" x-text="rec.matric_no"></div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#EBF5EC] text-[#2E7D32] flex items-center gap-1">
                                <i class="bi bi-check-circle-fill"></i> Verified
                            </span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </main>

    <!-- QR Code Script & Real-Time Polling -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        function projectorStream(initialCount, initialTotal) {
            return {
                checkedIn: initialCount,
                totalRoster: initialTotal,
                isFullscreen: false,
                recentRecords: @json($recentRecords),

                get percentage() {
                    if (this.totalRoster === 0) return 0;
                    return Math.min(100, Math.round((this.checkedIn / this.totalRoster) * 100));
                },

                init() {
                    // Generate QR code
                    const qrContainer = document.getElementById('qrcodeCanvas');
                    if (qrContainer && typeof QRCode !== 'undefined') {
                        new QRCode(qrContainer, {
                            text: "{{ route('public.checkin', $session->access_token) }}",
                            width: 250,
                            height: 250,
                            colorDark: "#1C1C1E",
                            colorLight: "#FFFFFF",
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    }

                    // Poll live stats every 3 seconds
                    setInterval(() => {
                        this.pollStats();
                    }, 3000);
                },

                async pollStats() {
                    try {
                        const res = await fetch("{{ route('sessions.live-stats', $session->id) }}");
                        if (res.ok) {
                            const data = await res.json();
                            this.checkedIn = data.checked_in_count;
                            if (data.recent && data.recent.length > 0) {
                                this.recentRecords = data.recent;
                            }
                        }
                    } catch (e) {
                        // Keep current view if network hiccups
                    }
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(() => {});
                        this.isFullscreen = true;
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                            this.isFullscreen = false;
                        }
                    }
                }
            };
        }
    </script>
</body>
</html>
