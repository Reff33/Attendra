<!DOCTYPE html>
<html lang="en" class="h-full bg-[#FAF7F2]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — Attendra Management System</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-[#FAF7F2]">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <a href="/" class="text-3xl font-bold tracking-tight text-[#1C1C1E] inline-flex items-center gap-1">
            Attendra<span class="text-[#F8A926]">.</span>
        </a>
        <h2 class="mt-3 text-lg font-bold text-[#1C1C1E]">Activity & Attendance Portal</h2>
        <p class="mt-1 text-xs text-[#8E8E93]">Universiti Teknikal Malaysia Melaka (UTeM)</p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div x-data="{ tab: 'staff' }" class="bg-white py-8 px-6 shadow-[0_6px_24px_rgba(0,0,0,0.04)] rounded-[28px] border border-[#F0EAE1] sm:px-8">
            <!-- Role Toggle Pills -->
            <div class="flex items-center bg-[#FAF7F2] p-1 rounded-2xl border border-[#F0EAE1] mb-6">
                <button type="button" @click="tab = 'staff'"
                        :class="tab === 'staff' ? 'bg-white text-[#1C1C1E] font-bold shadow-sm' : 'text-[#8E8E93] font-medium'"
                        class="flex-1 py-2 text-xs rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <i class="bi bi-person-badge"></i> Staff Organizer
                </button>
                <button type="button" @click="tab = 'admin'"
                        :class="tab === 'admin' ? 'bg-white text-[#1C1C1E] font-bold shadow-sm' : 'text-[#8E8E93] font-medium'"
                        class="flex-1 py-2 text-xs rounded-xl transition-all flex items-center justify-center gap-1.5">
                    <i class="bi bi-shield-lock"></i> System Admin
                </button>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="target_role" :value="tab">

                <div>
                    <label class="block text-xs font-bold text-[#1C1C1E] mb-1.5">Official University Email</label>
                    <input type="email" name="email" required
                           :value="tab === 'admin' ? 'admin@utem.edu.my' : 'faris.razali@utem.edu.my'"
                           class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all"
                           placeholder="staff@utem.edu.my">
                    @error('email')
                        <p class="mt-1 text-xs text-[#E07A5F]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-[#1C1C1E]">Password</label>
                        <span class="text-[11px] text-[#8E8E93]">Default: <code class="bg-[#FAF7F2] px-1 py-0.5 rounded">password123</code></span>
                    </div>
                    <input type="password" name="password" required value="password123"
                           class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all"
                           placeholder="••••••••">
                </div>

                <div class="space-y-2 text-xs pt-1">
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-[#8E8E93] cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded text-[#F8A926] focus:ring-[#F8A926]">
                            <span>Remember credentials</span>
                        </label>
                    </div>

                    @if(session('device_limit_reached'))
                        <div class="p-3 rounded-xl bg-[#FDE8E1] border border-[#E07A5F]/30 space-y-1.5">
                            <label class="flex items-start gap-2 font-bold text-xs text-[#E07A5F] cursor-pointer">
                                <input type="checkbox" name="revoke_others" value="1" checked class="rounded text-[#E07A5F] mt-0.5">
                                <span>Sign out of all other devices and continue signing in on this device</span>
                            </label>
                        </div>
                    @endif
                </div>

                <button type="submit"
                        class="w-full mt-2 py-3 px-4 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] font-bold text-xs shadow-sm transition-colors flex items-center justify-center gap-2">
                    <span>Enter Attendra Console</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <!-- Participant Notice (PRD §2.1 A02 / No Login Required) -->
            <div class="mt-6 pt-5 border-t border-[#F0EAE1] text-center">
                <div class="p-3.5 rounded-2xl bg-[#E8EAFA] border border-[#4C5270]/20 text-left">
                    <p class="text-xs font-bold text-[#4C5270] flex items-center gap-1.5">
                        <i class="bi bi-info-circle-fill"></i> Student / Participant Access
                    </p>
                    <p class="mt-1 text-[11px] text-[#4C5270]/90 leading-relaxed">
                        Students do not need an account. Please scan the QR code displayed at the event or click the link shared by your organizer.
                    </p>
                    <a href="{{ route('public.checkin', 'amanat-ftmk-2026') }}"
                       class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-bold text-[#4C5270] underline hover:opacity-80">
                        Try Demo Public Check-In Page ↗
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
