@extends('layouts.app', ['title' => 'Staff Camera Scanner'])

@push('styles')
<style>
    #reader {
        width: 100% !important;
        border: none !important;
    }
    #reader video {
        border-radius: 1.5rem !important;
        object-fit: cover !important;
        width: 100% !important;
        height: 100% !important;
    }
    #reader__scan_region {
        background: transparent !important;
    }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="staffScanner()">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('dashboard') }}" class="text-xs font-bold text-[#8E8E93] hover:text-[#1C1C1E] flex items-center gap-1 mb-1">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                Staff Camera Scanner (Live Intake)
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Scan student digital passes or matric card QR directly via webcam or tablet camera
            </p>
        </div>
    </div>

    <!-- Scanner Card -->
    <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-sm p-6 sm:p-8">
        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)] gap-6 items-start">
            <!-- Camera Viewfinder Box -->
            <div class="relative bg-[#1C1C1E] rounded-3xl overflow-hidden aspect-video flex flex-col items-center justify-center text-white shadow-inner">
                <!-- Reader canvas mount -->
                <div id="reader" class="w-full h-full z-10" x-show="isScanning"></div>

                <!-- Idle Viewfinder Overlay when not started -->
                <div x-show="!isScanning" class="flex flex-col items-center justify-center p-6 space-y-3 z-0">
                    <div class="w-20 h-20 rounded-full bg-white/10 flex items-center justify-center text-[#F8A926] text-3xl">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                    <div class="text-center">
                        <h3 class="text-sm font-bold text-white">Camera Not Active</h3>
                        <p class="text-[11px] text-white/70 max-w-xs mt-0.5">
                            Click the button below to turn on the staff tablet camera or webcam.
                        </p>
                    </div>
                    <button type="button" @click="startScanner()"
                            class="px-5 py-2.5 rounded-full bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                        <i class="bi bi-play-circle-fill"></i> Start Camera Scanner
                    </button>
                </div>

                <!-- Controls overlay when scanning -->
                <div x-show="isScanning" class="absolute bottom-3 left-0 right-0 z-20 flex items-center justify-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#2E7D32] animate-ping"></span>
                    <span class="text-[11px] text-white/90 font-medium px-3 py-1 rounded-full bg-black/60 backdrop-blur-xs">
                        Camera Active &bull; Align Student QR Code
                    </span>
                    <button type="button" @click="stopScanner()"
                            class="px-3 py-1 rounded-full bg-[#E07A5F] hover:bg-[#c96248] text-white text-[10px] font-bold shadow-xs">
                        Stop
                    </button>
                </div>
            </div>

            <!-- Right column: session, feedback, manual fallback -->
            <div class="space-y-5 min-w-0">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                        Target Active Session
                    </label>
                    <select x-model="selectedSession"
                            class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs font-bold text-[#1C1C1E] focus:outline-none focus:border-[#F8A926]">
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}">{{ $s->title }} — {{ $s->venue }} (OPEN)</option>
                        @endforeach
                    </select>
                </div>

                <!-- Scan Result Card Feedback -->
                <div x-show="scanResult" x-cloak
                     class="p-4 rounded-2xl border transition-all"
                     :class="scanResult?.status === 'checked_in_success' || scanResult?.status === 'verified_existing' ? 'bg-[#EBF5EC] border-[#2E7D32]/30' : (scanResult?.status === 'already_checked_in' ? 'bg-[#FEEFD0] border-[#F8A926]/40' : 'bg-[#FDE8E1] border-[#E07A5F]/30')">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-base"
                                      x-text="scanResult?.status === 'checked_in_success' || scanResult?.status === 'verified_existing' ? '✅' : (scanResult?.status === 'already_checked_in' ? '⚠️' : '❌')"></span>
                                <h4 class="text-xs font-extrabold text-[#1C1C1E]" x-text="scanResult?.title"></h4>
                            </div>
                            <div class="text-xs font-bold text-[#1C1C1E]" x-text="scanResult?.student_name"></div>
                            <div class="text-[11px] font-mono text-[#8E8E93]" x-text="scanResult?.matric_no"></div>
                            <p class="text-[11px] text-[#8E8E93]" x-text="scanResult?.message"></p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-white/80 font-bold text-[#1C1C1E]" x-text="scanResult?.token_hash"></span>
                            <div class="text-[10px] text-[#8E8E93] mt-1" x-text="scanResult?.time"></div>
                        </div>
                    </div>
                </div>

                <!-- Manual Quick Input Fallback -->
                <div class="pt-4 border-t border-[#F0EAE1] space-y-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E8E93]">
                        Or Scan with USB Barcode Reader / Enter Matric Number
                    </span>
                    <form @submit.prevent="submitPayload(manualInput)" class="flex items-center gap-2">
                        <input type="text" x-model="manualInput"
                               placeholder="e.g. B032110045 or ATT-4312"
                               class="flex-1 min-w-0 px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all font-mono">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-[#1C1C1E] hover:bg-black text-white text-xs font-bold transition-all shadow-xs shrink-0">
                            Verify
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    function staffScanner() {
        return {
            selectedSession: '{{ $sessions->first()?->id }}',
            isScanning: false,
            scanner: null,
            scanResult: null,
            manualInput: '',

            startScanner() {
                this.isScanning = true;
                this.scanResult = null;

                this.$nextTick(() => {
                    this.scanner = new Html5Qrcode("reader");
                    const config = { fps: 10, qrbox: { width: 220, height: 220 } };

                    this.scanner.start(
                        { facingMode: "environment" },
                        config,
                        (decodedText) => {
                            this.playBeep();
                            this.submitPayload(decodedText);
                        },
                        (errorMessage) => {
                            // ignore frame read errors
                        }
                    ).catch(err => {
                        alert("Unable to access camera: " + err);
                        this.isScanning = false;
                    });
                });
            },

            stopScanner() {
                if (this.scanner) {
                    this.scanner.stop().then(() => {
                        this.isScanning = false;
                        this.scanner.clear();
                    }).catch(() => {
                        this.isScanning = false;
                    });
                } else {
                    this.isScanning = false;
                }
            },

            async submitPayload(payload) {
                if (!payload || !this.selectedSession) return;

                try {
                    const res = await fetch("{{ route('quick-ops.scanner.scan') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            session_id: this.selectedSession,
                            qr_payload: payload
                        })
                    });

                    const data = await res.json();
                    this.scanResult = data;
                    this.manualInput = '';
                } catch (e) {
                    this.scanResult = {
                        status: 'error',
                        title: 'Connection Error',
                        message: 'Failed to contact server.'
                    };
                }
            },

            playBeep() {
                try {
                    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = "sine";
                    osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note
                    gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.15);
                } catch (e) {}
            }
        };
    }
</script>
@endpush
