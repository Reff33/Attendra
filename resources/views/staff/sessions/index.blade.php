@extends('layouts.app', ['title' => 'Sessions Management & Broadcast'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                Sessions Management & Broadcast
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Configure attendance verification policies, broadcast QR passes, and export records
            </p>
        </div>
        <a href="{{ route('sessions.create') }}"
           class="px-4 py-2 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 self-start sm:self-auto">
            <i class="bi bi-plus-lg"></i> Create New Session
        </a>
    </div>

    <!-- 2 Column Layout: Left Session List (D4) & Right Active QR / Share Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Sessions List Cards -->
        <div class="lg:col-span-7 space-y-4">
            @foreach($sessions as $s)
                <div class="bg-white rounded-[24px] p-5 border border-[#F0EAE1] shadow-sm hover:border-[#F8A926]/50 transition-all flex flex-col justify-between gap-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                @if($s->status === 'open')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#EBF5EC] text-[#2E7D32] flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#2E7D32] animate-pulse"></span>
                                        LIVE NOW
                                    </span>
                                @elseif($s->status === 'scheduled')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#E8EAFA] text-[#4C5270]">
                                        UPCOMING
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#FAF7F2] text-[#8E8E93]">
                                        CLOSED
                                    </span>
                                @endif
                                <span class="text-xs text-[#8E8E93]">• {{ $s->activity->faculty ?? 'FTMK' }}</span>
                            </div>

                            <h3 class="text-sm font-bold text-[#1C1C1E]">{{ $s->title }}</h3>
                            <p class="text-xs text-[#8E8E93]">
                                <i class="bi bi-geo-alt"></i> {{ $s->venue }}
                                <span class="mx-1">•</span>
                                <i class="bi bi-clock"></i> {{ $s->starts_at?->format('h:i A') ?? '08:30 AM' }} - {{ $s->ends_at?->format('h:i A') ?? '11:30 AM' }}
                            </p>
                        </div>

                        <!-- Action Button: Share QR / Toggle -->
                        <div class="flex flex-col items-end gap-2">
                            <a href="{{ route('public.checkin', $s->access_token) }}" target="_blank"
                               class="px-3 py-1.5 rounded-xl bg-[#FAF7F2] hover:bg-[#F0EAE1] text-xs font-bold text-[#1C1C1E] border border-[#F0EAE1] transition-colors flex items-center gap-1.5">
                                <i class="bi bi-box-arrow-up-right"></i> Open Form
                            </a>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('sessions.duplicate', $s->id) }}">
                                    @csrf
                                    <button type="submit"
                                            title="Duplicate this session configuration"
                                            class="text-[11px] font-bold text-[#8E8E93] hover:text-[#1C1C1E] flex items-center gap-1">
                                        <i class="bi bi-copy"></i> Duplicate
                                    </button>
                                </form>
                                <span class="text-[#F0EAE1]">•</span>
                                <form method="POST" action="{{ route('sessions.toggle', $s->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-[11px] font-semibold {{ $s->status === 'open' ? 'text-[#E07A5F] hover:underline' : 'text-[#2E7D32] hover:underline' }}">
                                        {{ $s->status === 'open' ? 'Force Close Session' : 'Re-Open Session' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Meta & Verification Policy Badge -->
                    <div class="pt-3 border-t border-[#F0EAE1] flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-[#8E8E93]">Verification:</span>
                            <span class="font-bold text-[#1C1C1E] bg-[#FAF7F2] px-2 py-0.5 rounded-lg border border-[#F0EAE1]">
                                @if($s->verification_mode === 'ic_and_gps')
                                    4-Digit IC + GPS ({{ $s->geofence_radius_meters }}m)
                                @elseif($s->verification_mode === 'gps')
                                    GPS Geofence Only
                                @else
                                    4-Digit IC Only
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-[#2E7D32]">
                                {{ $s->attendanceRecords->count() }} Checked In
                            </span>
                            <a href="{{ route('sessions.export', $s->id) }}" class="text-[#F8A926] font-bold hover:underline">
                                Export CSV
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Right: Active QR Code & Poster Card (FigCraft D4) -->
        <div class="lg:col-span-5">
            <div class="bg-white rounded-[28px] p-6 border border-[#F0EAE1] shadow-sm sticky top-[98px] space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E8E93]">BROADCAST PASS</span>
                        <h3 class="text-sm font-bold text-[#1C1C1E] mt-0.5">
                            {{ $liveSession?->title ?? 'Session QR & Public Link' }}
                        </h3>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#FEEFD0] text-[#7A521E]">
                        Digital QR
                    </span>
                </div>

                <!-- QR Display Container (Real Canvas Generated QR) -->
                <div class="bg-[#FAF7F2] border-2 border-dashed border-[#F8A926]/40 rounded-2xl p-6 flex flex-col items-center justify-center text-center">
                    <div class="p-3 bg-white rounded-2xl shadow-sm border border-[#EAE6DF] flex flex-col items-center justify-center">
                        <div id="sessionQrCanvas" class="flex items-center justify-center min-w-[180px] min-h-[180px]"></div>
                        <span class="text-[10px] font-bold text-[#8E8E93] mt-2 font-mono uppercase tracking-widest">
                            ATTENDRA &bull; PASS
                        </span>
                        <span class="text-[9px] text-[#2E7D32] font-semibold flex items-center gap-1 mt-0.5">
                            <i class="bi bi-shield-check text-[#2E7D32]"></i> Real-Time Anti-Proxy
                        </span>
                    </div>

                    <p class="text-[11px] text-[#8E8E93] mt-3 max-w-xs leading-relaxed">
                        Live dynamic QR pass. Students can scan directly via smartphone camera.
                    </p>
                </div>

                <!-- Public URL copy bar -->
                <div class="space-y-1.5">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-[#8E8E93]">
                        Public Student Check-In Link
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly
                               value="{{ route('public.checkin', $liveSession?->access_token ?? 'amanat-ftmk-2026') }}"
                               class="flex-1 px-3 py-2 bg-[#FAF7F2] rounded-xl border border-[#F0EAE1] text-xs font-mono text-[#1C1C1E] select-all focus:outline-none">
                        <button onclick="navigator.clipboard.writeText('{{ route('public.checkin', $liveSession?->access_token ?? 'amanat-ftmk-2026') }}'); alert('Link copied to clipboard!');"
                                class="px-3.5 py-2 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-xs font-bold text-[#1C1C1E] transition-colors shrink-0">
                            Copy
                        </button>
                    </div>
                </div>

                <!-- Actions: Projector Mode & Mobile Preview -->
                <div class="space-y-2 pt-2 border-t border-[#F0EAE1]">
                    @if($liveSession)
                        <a href="{{ route('sessions.broadcast', $liveSession->id) }}" target="_blank"
                           class="w-full py-3 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-xs font-bold text-[#1C1C1E] shadow-sm transition-all flex items-center justify-center gap-2">
                            <i class="bi bi-projector-fill text-sm"></i>
                            <span>📽️ Buka Mod Projektor Skrin Penuh</span>
                        </a>
                    @endif
                    <a href="{{ route('public.checkin', $liveSession?->access_token ?? 'amanat-ftmk-2026') }}" target="_blank"
                       class="w-full py-2.5 rounded-xl bg-[#FAF7F2] hover:bg-[#F0EAE1] text-xs font-bold text-[#1C1C1E] border border-[#F0EAE1] transition-colors flex items-center justify-center gap-2">
                        <i class="bi bi-phone"></i> Preview Mobile Check-In Form ↗
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const qrContainer = document.getElementById('sessionQrCanvas');
        if (qrContainer && typeof QRCode !== 'undefined') {
            new QRCode(qrContainer, {
                text: "{{ route('public.checkin', $liveSession?->access_token ?? 'amanat-ftmk-2026') }}",
                width: 170,
                height: 170,
                colorDark: "#1C1C1E",
                colorLight: "#FFFFFF",
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    });
</script>
@endpush
@endsection
