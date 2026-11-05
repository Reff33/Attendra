@extends('layouts.app', ['title' => 'Staff Profile & Account Settings'])

@section('content')
<div class="w-full space-y-6">
    <!-- Breadcrumb & Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('dashboard') }}" class="text-xs font-bold text-[#8E8E93] hover:text-[#1C1C1E] flex items-center gap-1.5 mb-1">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                Staff Profile & Account Settings
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Manage staff credentials, password security, and active device sessions
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-full text-xs font-bold {{ $user->isAdmin() ? 'bg-[#FEEFD0] text-[#7A521E] border border-[#F8A926]/40' : 'bg-[#EBF5EC] text-[#2E7D32] border border-[#2E7D32]/30' }} flex items-center gap-2 shadow-2xs">
                <span class="w-2 h-2 rounded-full {{ $user->isAdmin() ? 'bg-[#F8A926]' : 'bg-[#2E7D32]' }} animate-pulse"></span>
                <span>{{ strtoupper($user->role) }}</span>
                <span class="text-[10px] opacity-70">&bull; {{ strtoupper($user->status ?? 'ACTIVE') }}</span>
            </span>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-[#EBF5EC] border border-[#2E7D32]/30 flex items-center gap-3 text-xs font-bold text-[#2E7D32] shadow-2xs">
            <i class="bi bi-check-circle-fill text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-[#FDE8E1] border border-[#E07A5F]/30 flex items-center gap-3 text-xs font-bold text-[#E07A5F] shadow-2xs">
            <i class="bi bi-exclamation-triangle-fill text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-[#FDE8E1] border border-[#E07A5F]/30 space-y-1 shadow-2xs">
            <div class="flex items-center gap-2 text-xs font-bold text-[#E07A5F]">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Please review the following errors:</span>
            </div>
            <ul class="list-disc list-inside text-[11px] text-[#E07A5F] pl-4">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Profile Hero Identity & Stats Card (Fluid Full Width) -->
    <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-2xs p-6 sm:p-7 space-y-6">
        <!-- Top Identity Row -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            <div class="flex items-center gap-4">
                <!-- Avatar Circle -->
                <div class="w-16 h-16 rounded-2xl bg-[#FEEFD0] border-2 border-[#F8A926]/50 flex items-center justify-center text-2xl font-black text-[#7A521E] shadow-2xs shrink-0">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="text-xl font-bold text-[#1C1C1E]">{{ $user->name }}</h2>
                        <span class="px-2.5 py-0.5 rounded-lg bg-[#FAF7F2] border border-[#F0EAE1] text-[11px] font-mono font-bold text-[#1C1C1E]">
                            {{ $user->staff_id ?? 'STF-001' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-[#8E8E93] flex-wrap">
                        <span class="flex items-center gap-1.5"><i class="bi bi-envelope"></i> {{ $user->email }}</span>
                        <span>&bull;</span>
                        <span class="flex items-center gap-1.5"><i class="bi bi-building"></i> {{ $user->faculty ?? 'FTMK' }}</span>
                    </div>
                </div>
            </div>

            <div class="text-left sm:text-right text-[11px] text-[#8E8E93] border-t sm:border-t-0 pt-2 sm:pt-0 border-[#F0EAE1]">
                <div>Account Registered</div>
                <div class="font-bold text-[#1C1C1E] mt-0.5">{{ $user->created_at?->format('d M Y, h:i A') ?? '05 Nov 2026' }}</div>
            </div>
        </div>

        <!-- 3 Horizontal Stats Cards (Responsive 1 to 3 columns) -->
        <div class="pt-5 border-t border-[#F0EAE1] grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Activities Card (Butter/Gold) -->
            <div class="p-4 rounded-2xl bg-[#FAF7F2] border border-[#F0EAE1] flex items-center gap-3.5 hover:border-[#F8A926]/40 transition-all">
                <div class="w-10 h-10 rounded-xl bg-[#FEEFD0] border border-[#F8A926]/40 flex items-center justify-center text-[#7A521E] text-base shrink-0">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <div class="text-xl font-black text-[#1C1C1E] leading-none">{{ $activitiesCount }}</div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-[#8E8E93] mt-1">Managed Activities</div>
                </div>
            </div>

            <!-- Sessions Card (Lavender) -->
            <div class="p-4 rounded-2xl bg-[#FAF7F2] border border-[#F0EAE1] flex items-center gap-3.5 hover:border-[#4C5270]/40 transition-all">
                <div class="w-10 h-10 rounded-xl bg-[#E8EAFA] border border-[#4C5270]/20 flex items-center justify-center text-[#4C5270] text-base shrink-0">
                    <i class="bi bi-compass"></i>
                </div>
                <div>
                    <div class="text-xl font-black text-[#1C1C1E] leading-none">{{ $sessionsCount }}</div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-[#8E8E93] mt-1">Attendance Sessions</div>
                </div>
            </div>

            <!-- Check-Ins Card (Mint/Sage) -->
            <div class="p-4 rounded-2xl bg-[#FAF7F2] border border-[#F0EAE1] flex items-center gap-3.5 hover:border-[#2E7D32]/40 transition-all">
                <div class="w-10 h-10 rounded-xl bg-[#E5F0E6] border border-[#2E7D32]/20 flex items-center justify-center text-[#2E7D32] text-base shrink-0">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div>
                    <div class="text-xl font-black text-[#2E7D32] leading-none">{{ $totalAttendance }}</div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-[#8E8E93] mt-1">Student Check-Ins</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 Column Equal Symmetrical Grid (50% - 50% split on Desktop, Stacked on Mobile/Tablet) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        <!-- ================= LEFT COLUMN ================= -->
        <div class="space-y-6">

            <!-- Card 1: Personal Profile Information -->
            <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-2xs p-6 sm:p-7 space-y-6">
                <div class="pb-3 border-b border-[#F0EAE1]">
                    <h3 class="text-sm font-bold text-[#1C1C1E] flex items-center gap-2">
                        <i class="bi bi-person-lines-fill text-[#F8A926]"></i>
                        <span>Personal Profile Information</span>
                    </h3>
                    <p class="text-xs text-[#8E8E93] mt-0.5">
                        Update your official staff name, university email address, and phone number
                    </p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Row 1: Name & Staff ID -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                                Full Name <span class="text-[#E07A5F]">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs font-bold text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#8E8E93] mb-1.5 flex items-center justify-between">
                                <span>Staff ID</span>
                                <span class="text-[10px] text-[#8E8E93] font-normal"><i class="bi bi-lock-fill"></i> Locked</span>
                            </label>
                            <input type="text" value="{{ $user->staff_id ?? 'STF-001' }}" readonly disabled
                                   class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs font-mono font-bold text-[#8E8E93] cursor-not-allowed">
                        </div>
                    </div>

                    <!-- Row 2: Email & Phone -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                                Official University Email <span class="text-[#E07A5F]">*</span>
                            </label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs font-mono text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                                Phone Number
                            </label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+6013-9876543"
                                   class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs font-mono text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                        </div>
                    </div>

                    <!-- Row 3: Faculty / Department -->
                    <div>
                        <label for="faculty" class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                            Faculty / Managing Department
                        </label>
                        <select id="faculty" name="faculty"
                                class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs font-bold text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                            <option value="FTMK" {{ old('faculty', $user->faculty) === 'FTMK' ? 'selected' : '' }}>FTMK — Faculty of Information and Communication Technology</option>
                            <option value="FKE" {{ old('faculty', $user->faculty) === 'FKE' ? 'selected' : '' }}>FKE — Faculty of Electrical Engineering</option>
                            <option value="FKEKK" {{ old('faculty', $user->faculty) === 'FKEKK' ? 'selected' : '' }}>FKEKK — Faculty of Electronics and Computer Engineering</option>
                            <option value="FKM" {{ old('faculty', $user->faculty) === 'FKM' ? 'selected' : '' }}>FKM — Faculty of Mechanical Engineering</option>
                            <option value="FKP" {{ old('faculty', $user->faculty) === 'FKP' ? 'selected' : '' }}>FKP — Faculty of Manufacturing Engineering</option>
                            <option value="FPTT" {{ old('faculty', $user->faculty) === 'FPTT' ? 'selected' : '' }}>FPTT — Faculty of Technology Management and Technopreneurship</option>
                            <option value="Pusat Bahasa" {{ old('faculty', $user->faculty) === 'Pusat Bahasa' ? 'selected' : '' }}>PBPI — Centre for Languages and Human Development</option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-[#F0EAE1] flex justify-end">
                        <button type="submit"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5">
                            <i class="bi bi-check2"></i> Save Profile Information
                        </button>
                    </div>
                </form>
            </div>

            <!-- Card 2: Active Device Sessions Management (Max 3 Devices Limit) -->
            <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-2xs p-6 sm:p-7 space-y-5">
                <div class="flex items-start justify-between gap-3 pb-3 border-b border-[#F0EAE1]">
                    <div>
                        <h3 class="text-sm font-bold text-[#1C1C1E] flex items-center gap-2">
                            <i class="bi bi-devices text-[#F8A926]"></i>
                            <span>Active Device Sessions</span>
                        </h3>
                        <p class="text-xs text-[#8E8E93] mt-0.5">
                            Manage active signed-in devices (Strict limit of 3 concurrent devices)
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $deviceSessions->count() >= 3 ? 'bg-[#FDE8E1] text-[#E07A5F] border border-[#E07A5F]/30' : 'bg-[#EBF5EC] text-[#2E7D32] border border-[#2E7D32]/30' }} shrink-0">
                        {{ $deviceSessions->count() }} / 3 Devices in Use
                    </span>
                </div>

                <!-- Active Devices List -->
                <div class="space-y-3">
                    @forelse($deviceSessions as $sess)
                        <div class="p-3.5 rounded-2xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $sess->is_current ? 'bg-[#FAF7F2] border-[#F8A926]/40 shadow-2xs' : 'bg-white border-[#F0EAE1]' }}">
                            <div class="flex items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0 {{ $sess->is_current ? 'bg-[#FEEFD0] text-[#7A521E]' : 'bg-[#FAF7F2] text-[#8E8E93]' }}">
                                    <i class="bi {{ $sess->icon }}"></i>
                                </div>
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-xs font-bold text-[#1C1C1E]">{{ $sess->device_type }}</h4>
                                        @if($sess->is_current)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#EBF5EC] text-[#2E7D32] inline-flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#2E7D32]"></span> Current Device
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-[#8E8E93] flex items-center gap-2 flex-wrap">
                                        <span>{{ $sess->browser }}</span>
                                        <span>&bull;</span>
                                        <span class="font-mono text-[10px]">{{ $sess->ip_address }}</span>
                                        <span>&bull;</span>
                                        <span>Active {{ $sess->last_active }}</span>
                                    </p>
                                </div>
                            </div>

                            @if(!$sess->is_current)
                                <form method="POST" action="{{ route('profile.sessions.revoke', $sess->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            onclick="return confirm('Are you sure you want to sign out this device session?')"
                                            class="w-full sm:w-auto px-3 py-1.5 rounded-xl bg-[#FAF7F2] hover:bg-[#FDE8E1] text-[#E07A5F] text-[11px] font-bold border border-[#F0EAE1] hover:border-[#E07A5F]/40 transition-colors">
                                        Sign Out
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-4 text-xs text-[#8E8E93]">
                            No active device sessions found.
                        </div>
                    @endforelse
                </div>

                <!-- Footer Revoke All Other Devices CTA -->
                @if($deviceSessions->where('is_current', false)->isNotEmpty())
                    <div class="pt-3 border-t border-[#F0EAE1] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <span class="text-[11px] text-[#8E8E93]">
                            Sign out of other devices if you left your account signed in on a public workstation.
                        </span>
                        <form method="POST" action="{{ route('profile.sessions.revoke-others') }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('Are you sure you want to sign out of all other devices?')"
                                    class="w-full sm:w-auto px-4 py-2 rounded-xl bg-[#FAF7F2] hover:bg-[#FDE8E1] text-[#E07A5F] text-xs font-bold border border-[#F0EAE1] hover:border-[#E07A5F]/40 transition-colors shrink-0">
                                <i class="bi bi-box-arrow-right"></i> Sign Out All Other Devices
                            </button>
                        </form>
                    </div>
                @else
                    <div class="pt-2 border-t border-[#F0EAE1] text-[11px] text-[#8E8E93]">
                        <i class="bi bi-shield-check text-[#2E7D32]"></i> Your account is secure. No other active device sessions detected.
                    </div>
                @endif
            </div>

        </div>

        <!-- ================= RIGHT COLUMN ================= -->
        <div class="space-y-6">

            <!-- Card 1: Password Security -->
            <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-2xs p-6 sm:p-7 space-y-5">
                <div class="pb-3 border-b border-[#F0EAE1]">
                    <h3 class="text-sm font-bold text-[#1C1C1E] flex items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-[#F8A926]"></i>
                        <span>Password Security</span>
                    </h3>
                    <p class="text-xs text-[#8E8E93] mt-0.5">
                        Change your password regularly to keep your account protected
                    </p>
                </div>

                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                            Current Password <span class="text-[#E07A5F]">*</span>
                        </label>
                        <input type="password" id="current_password" name="current_password" required
                               placeholder="••••••••"
                               class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                            New Password <span class="text-[#E07A5F]">*</span>
                        </label>
                        <input type="password" id="password" name="password" required
                               placeholder="Minimum 8 characters"
                               class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                            Confirm New Password <span class="text-[#E07A5F]">*</span>
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               placeholder="Retype new password"
                               class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                    </div>

                    <div class="pt-1">
                        <button type="submit"
                                class="w-full py-2.5 rounded-xl bg-[#1C1C1E] hover:bg-black text-white text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5">
                            <i class="bi bi-shield-lock"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Card 2: Account Activity Log -->
            <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-2xs p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#F0EAE1]">
                    <h4 class="text-xs font-bold text-[#1C1C1E] uppercase tracking-wider flex items-center gap-1.5">
                        <i class="bi bi-clock-history text-[#8E8E93]"></i>
                        <span>Account Activity Log</span>
                    </h4>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.audit.index') }}" class="text-[11px] font-bold text-[#F8A926] hover:underline">
                            View All (Admin)
                        </a>
                    @endif
                </div>

                <div class="space-y-2.5">
                    @forelse($recentLogs as $log)
                        <div class="p-3 rounded-2xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-[#1C1C1E] font-mono text-[11px] px-2 py-0.5 rounded bg-white border border-[#EAE6DF]">
                                    {{ $log->action_type }}
                                </span>
                                <span class="text-[10px] text-[#8E8E93]">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-[11px] text-[#8E8E93] leading-relaxed pt-0.5">{{ $log->details }}</p>
                        </div>
                    @empty
                        <div class="text-center py-4 text-xs text-[#8E8E93]">
                            No recent activity records found.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
