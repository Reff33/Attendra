@extends('layouts.app', ['title' => 'Staff Directory & Provisioning'])

@section('content')
<div class="space-y-6" x-data="{ addStaffModal: false, resetModal: false, selectedStaff: null, resetPasswordUrl: '' }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                Staff Directory & Account Provisioning
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Manage university organizers, assign faculty scopes, and control system credentials
            </p>
        </div>
        <button @click="addStaffModal = true"
                class="px-4 py-2 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 self-start sm:self-auto">
            <i class="bi bi-person-plus-fill"></i> Add New Staff
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-[#EBF5EC] border border-[#2E7D32]/20 text-[#2E7D32] text-xs font-medium flex items-center gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-[#FDE8E1] border border-[#E07A5F]/20 text-[#E07A5F] text-xs font-medium flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Staff Table Card (FigCraft D10) -->
    <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-sm overflow-hidden p-6 space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#FAF7F2] text-[#8E8E93] font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4 rounded-l-xl">STAFF NAME & EMAIL</th>
                        <th class="py-3 px-4">STAFF ID</th>
                        <th class="py-3 px-4">FACULTY</th>
                        <th class="py-3 px-4">MANAGED ACTIVITIES</th>
                        <th class="py-3 px-4">ACCOUNT STATUS</th>
                        <th class="py-3 px-4">JOINED DATE</th>
                        <th class="py-3 px-4 rounded-r-xl text-right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#FAF7F2]">
                    @forelse($staffMembers as $staff)
                        <tr class="hover:bg-[#FAF7F2]/60 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-[#1C1C1E]">
                                {{ $staff->name }}
                                <div class="text-[11px] font-normal text-[#8E8E93]">{{ $staff->email }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold text-[#1C1C1E]">
                                {{ $staff->staff_id ?? 'UTEM-STAFF' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#E8EAFA] text-[#4C5270]">
                                    {{ $staff->faculty ?? 'FTMK' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-[#1C1C1E]">
                                {{ $staff->activities_count }} Activities
                            </td>
                            <td class="py-3.5 px-4">
                                @if($staff->status === 'active')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#EBF5EC] text-[#2E7D32]">
                                        ● Active
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#FDE8E1] text-[#E07A5F]">
                                        ● Suspended
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-[#8E8E93]">
                                {{ $staff->created_at->format('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <!-- Reset Password Trigger -->
                                    <button type="button"
                                            @click="selectedStaff = { id: {{ $staff->id }}, name: '{{ addslashes($staff->name) }}', email: '{{ addslashes($staff->email) }}' }; resetPasswordUrl = '{{ route('admin.staff.reset-password', $staff->id) }}'; resetModal = true"
                                            class="px-2.5 py-1 rounded-lg bg-[#FAF7F2] hover:bg-[#F0EAE1] text-[#1C1C1E] border border-[#F0EAE1] text-[11px] font-bold flex items-center gap-1 transition-colors"
                                            title="Reset Password">
                                        <i class="bi bi-key-fill text-[#F8A926]"></i> Reset Key
                                    </button>

                                    <!-- Status Toggle Form -->
                                    <form method="POST" action="{{ route('admin.staff.toggle', $staff->id) }}" class="inline"
                                          onsubmit="return confirm('Are you sure you want to {{ $staff->status === 'active' ? 'suspend' : 'activate' }} {{ addslashes($staff->name) }}?');">
                                        @csrf
                                        @if($staff->status === 'active')
                                            <button type="submit"
                                                    class="px-2.5 py-1 rounded-lg bg-[#FDE8E1] hover:bg-[#fbd3c7] text-[#E07A5F] text-[11px] font-bold flex items-center gap-1 transition-colors"
                                                    title="Suspend Staff Account">
                                                <i class="bi bi-slash-circle"></i> Suspend
                                            </button>
                                        @else
                                            <button type="submit"
                                                    class="px-2.5 py-1 rounded-lg bg-[#EBF5EC] hover:bg-[#d8eedb] text-[#2E7D32] text-[11px] font-bold flex items-center gap-1 transition-colors"
                                                    title="Reactivate Staff Account">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-[#8E8E93]">
                                No staff members registered.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($staffMembers->hasPages())
            <div class="pt-4 border-t border-[#F0EAE1]">
                {{ $staffMembers->links() }}
            </div>
        @endif
    </div>

    <!-- Add Staff Modal (Alpine.js) -->
    <div x-show="addStaffModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div @click.away="addStaffModal = false"
             class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-2xl w-full max-w-lg p-6 sm:p-8 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#F0EAE1]">
                <div>
                    <h3 class="text-base font-bold text-[#1C1C1E]">Provision New Staff Organizer</h3>
                    <p class="text-xs text-[#8E8E93]">Add a university staff account with organizer privileges</p>
                </div>
                <button @click="addStaffModal = false" class="text-[#8E8E93] hover:text-[#1C1C1E]">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                        Full Name
                    </label>
                    <input type="text" name="name" required placeholder="e.g. Ts. Dr. Zulkifli Saad"
                           class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926]">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                            Staff ID
                        </label>
                        <input type="text" name="staff_id" required placeholder="e.g. S44120"
                               class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                            Faculty
                        </label>
                        <select name="faculty" required
                                class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926]">
                            <option value="FTMK">FTMK</option>
                            <option value="FKE">FKE</option>
                            <option value="FKM">FKM</option>
                            <option value="FKEKK">FKEKK</option>
                            <option value="FTKEE">FTKEE</option>
                            <option value="FPTT">FPTT</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                        Official Email
                    </label>
                    <input type="email" name="email" required placeholder="e.g. zulkifli.saad@utem.edu.my"
                           class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926]">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                        Initial Password
                    </label>
                    <input type="password" name="password" required value="password123"
                           class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926]">
                </div>

                <div class="pt-3 border-t border-[#F0EAE1] flex items-center justify-end gap-3">
                    <button type="button" @click="addStaffModal = false"
                            class="px-4 py-2 rounded-xl bg-[#FAF7F2] text-xs font-bold text-[#1C1C1E] border border-[#F0EAE1]">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold transition-all shadow-sm">
                        Create Staff Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Staff Password Modal (Alpine.js) -->
    <div x-show="resetModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs">
        <div @click.away="resetModal = false"
             class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-2xl w-full max-w-md p-6 sm:p-8 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-[#F0EAE1]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-[#FEEFD0] flex items-center justify-center text-[#F8A926]">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-[#1C1C1E]">Reset Staff Password</h3>
                        <p class="text-xs text-[#8E8E93]" x-text="selectedStaff ? selectedStaff.name : ''"></p>
                    </div>
                </div>
                <button @click="resetModal = false" class="text-[#8E8E93] hover:text-[#1C1C1E]">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form method="POST" :action="resetPasswordUrl" class="space-y-4">
                @csrf

                <div class="p-3 bg-[#FAF7F2] rounded-xl border border-[#F0EAE1] text-[11px] text-[#8E8E93] space-y-1">
                    <div class="font-bold text-[#1C1C1E] flex items-center gap-1">
                        <i class="bi bi-shield-lock-fill text-[#F8A926]"></i> Security Precaution
                    </div>
                    <p>Resetting the password will automatically revoke all active sessions for this staff member across all logged-in devices.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                        New Password
                    </label>
                    <input type="password" name="password" required minlength="6" placeholder="Enter new password (min. 6 characters)"
                           class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white">
                </div>

                <div class="pt-3 border-t border-[#F0EAE1] flex items-center justify-end gap-3">
                    <button type="button" @click="resetModal = false"
                            class="px-4 py-2 rounded-xl bg-[#FAF7F2] text-xs font-bold text-[#1C1C1E] border border-[#F0EAE1]">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-[#1C1C1E] hover:bg-black text-white text-xs font-bold transition-all shadow-sm">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
