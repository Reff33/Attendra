@extends('layouts.app', ['title' => 'Manual Walk-In Registration'])

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('dashboard') }}" class="text-xs font-bold text-[#8E8E93] hover:text-[#1C1C1E] flex items-center gap-1 mb-1">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                Manual Walk-In Registration (Staff Override)
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Register unlisted participants or students with device issues directly with audit trail logging
            </p>
        </div>
    </div>

    <!-- Manual Form Card (FigCraft D7) -->
    <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-sm p-6 sm:p-8 space-y-6">
        <div class="p-4 rounded-2xl bg-[#FDE8E1] border border-[#E07A5F]/20 text-[#1C1C1E] text-xs flex items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill text-[#E07A5F] text-base shrink-0 mt-0.5"></i>
            <div>
                <strong class="font-bold">Staff Override Protocol (PRD §2.1 V03):</strong>
                <p class="text-[11px] text-[#8E8E93] mt-0.5 leading-relaxed">
                    All manual submissions will be permanently recorded in the SHA-256 audit log with your staff ID and specified justification.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('quick-ops.walk-in.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                    Target Session
                </label>
                <select name="session_id" required
                        class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926]">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}">{{ $s->title }} — {{ $s->venue }} (OPEN)</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                    Student Full Name
                </label>
                <input type="text" name="student_name" required
                       placeholder="e.g. MOHD KHAIRUL BIN ANUAR"
                       class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926]">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                    Matric Card Number
                </label>
                <input type="text" name="matric_no" required
                       placeholder="e.g. B032110999"
                       class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] uppercase">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                    Justification / Reason for Manual Override
                </label>
                <select name="override_reason" required
                        class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926]">
                    <option value="Battery depleted / Phone offline">Battery depleted / Phone offline</option>
                    <option value="Camera malfunction / QR scan error">Camera malfunction / QR scan error</option>
                    <option value="GPS location permission disabled by OS">GPS location permission disabled by OS</option>
                    <option value="Late roster inclusion / Faculty guest">Late roster inclusion / Faculty guest</option>
                </select>
            </div>

            <div class="pt-4 border-t border-[#F0EAE1] flex items-center justify-end gap-3">
                <a href="{{ route('dashboard') }}"
                   class="px-5 py-2.5 rounded-xl bg-[#FAF7F2] hover:bg-[#F0EAE1] text-xs font-bold text-[#1C1C1E] border border-[#F0EAE1]">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                    <i class="bi bi-check-circle"></i> Authorize & Log Override
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
