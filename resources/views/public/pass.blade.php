<!DOCTYPE html>
<html lang="en" class="h-full bg-[#FAF7F2]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Attendance Verified — {{ $record->student_name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-center py-8 px-4 sm:px-6 bg-[#FAF7F2]">
    <div class="max-w-md mx-auto w-full space-y-6">
        <!-- Success State Brand Badge -->
        <div class="text-center space-y-1">
            <span class="text-2xl font-bold tracking-tight text-[#1C1C1E] inline-flex items-center gap-1">
                Attendra<span class="text-[#F8A926]">.</span>
            </span>
            <p class="text-xs text-[#8E8E93]">Official Attendance Verification Pass</p>
        </div>

        <!-- Digital Boarding Pass Ticket Card (FigCraft Warm Organic Aesthetic) -->
        <div class="bg-white rounded-[32px] border border-[#F0EAE1] shadow-xl overflow-hidden relative">
            <!-- Top Green Accent Header -->
            <div class="bg-[#EBF5EC] p-6 text-center border-b border-[#2E7D32]/10 space-y-2">
                <div class="w-14 h-14 bg-white rounded-full mx-auto flex items-center justify-center shadow-xs text-[#2E7D32]">
                    <i class="bi bi-check-lg text-3xl font-bold"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-[#2E7D32]">Attendance Verified!</h2>
                    <p class="text-xs text-[#2E7D32]/80 mt-0.5">Recorded in university attendance ledger</p>
                </div>
            </div>

            <!-- Ticket Body -->
            <div class="p-6 space-y-5">
                <!-- Student Details -->
                <div class="space-y-1 text-center">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93]">PARTICIPANT</span>
                    <h3 class="text-lg font-bold text-[#1C1C1E]">{{ $record->student_name }}</h3>
                    <p class="text-xs font-mono font-semibold text-[#8E8E93]">{{ $record->matric_no }}</p>
                </div>

                <!-- Session & Venue Details -->
                <div class="p-4 rounded-2xl bg-[#FAF7F2] border border-[#F0EAE1] space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[#8E8E93]">Event / Session:</span>
                        <span class="font-bold text-[#1C1C1E] text-right">{{ $session->title }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[#8E8E93]">Venue:</span>
                        <span class="font-semibold text-[#1C1C1E]">{{ $session->venue }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[#8E8E93]">Timestamp:</span>
                        <span class="font-medium text-[#1C1C1E]">{{ $record->checked_in_at->format('d M Y, h:i:s A') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[#8E8E93]">Verification:</span>
                        <span class="font-bold text-[#2E7D32] flex items-center gap-1">
                            <i class="bi bi-shield-check text-[#2E7D32]"></i> {{ strtoupper($record->verification_method) }}
                        </span>
                    </div>
                </div>

                <!-- Digital Token Pass Box -->
                <div class="border-2 border-dashed border-[#F0EAE1] rounded-2xl p-4 text-center space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93]">DIGITAL VERIFICATION TOKEN</span>
                    <div class="font-mono text-base font-extrabold text-[#1C1C1E] tracking-wider">
                        {{ $record->token_hash }}
                    </div>
                    <p class="text-[9px] text-[#8E8E93]">Keep this pass or save a copy as official proof of attendance.</p>
                </div>

                <!-- Print / Save Slip Action Button -->
                <div class="pt-2">
                    <button type="button" onclick="window.print()"
                            class="w-full py-3 px-4 rounded-xl bg-[#1C1C1E] hover:bg-black text-white font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-2">
                        <i class="bi bi-printer-fill text-[#F8A926]"></i> Print / Save Attendance Receipt
                    </button>
                </div>
            </div>

            <!-- Bottom Perforation Decor -->
            <div class="bg-[#FAF7F2] py-3 text-center border-t border-[#F0EAE1]">
                <span class="text-[10px] font-mono text-[#8E8E93] uppercase tracking-widest">
                    ATTENDRA • SECURED ATTENDANCE PROOF
                </span>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('public.checkin', $session->access_token) }}"
               class="text-xs font-semibold text-[#8E8E93] hover:text-[#1C1C1E] underline">
                &larr; Submit another check-in
            </a>
        </div>
    </div>
</body>
</html>
