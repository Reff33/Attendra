@extends('layouts.app', ['title' => 'Rosters & Eligible Datasets'])

@section('content')
<div class="space-y-6" x-data="rosterImporter()">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                Rosters & Eligible Datasets
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Enterprise multi-column CSV intake with interactive header mapping, data validation & SHA-256 audit freeze
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('rosters.template') }}"
               class="px-3.5 py-2 rounded-xl bg-white hover:bg-[#FAF7F2] text-[#1C1C1E] text-xs font-bold border border-[#F0EAE1] shadow-sm transition-all flex items-center gap-1.5">
                <i class="bi bi-file-earmark-arrow-down"></i> Download CSV Template
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-[#EBF5EC] border border-[#2E7D32]/20 text-[#2E7D32] text-xs font-medium flex items-center gap-2">
            <i class="bi bi-check-circle-fill text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-2xl bg-[#FDE8E1] border border-[#E07A5F]/20 text-[#E07A5F] text-xs font-medium flex items-center gap-2">
            <i class="bi bi-exclamation-circle-fill text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- 2 Column Layout: Left Uploader & Right Datatable (FigCraft D5) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Upload Dataset Card -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-white rounded-[28px] p-6 border border-[#F0EAE1] shadow-sm space-y-5">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E8E93]">DATASET INTAKE</span>
                    <h3 class="text-sm font-bold text-[#1C1C1E] mt-0.5">Upload Participant CSV</h3>
                    <p class="text-[11px] text-[#8E8E93] mt-1 leading-relaxed">
                        Interactive column mapper with auto-detection. Supports any column order without hardcoded row assumptions.
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                            Target Activity <span class="text-[#E07A5F]">*</span>
                        </label>
                        <select x-model="activityId"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] transition-all">
                            @foreach($activities as $act)
                                <option value="{{ $act->id }}" {{ $selectedActivity?->id === $act->id ? 'selected' : '' }}>
                                    {{ $act->title }} ({{ $act->faculty }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-1.5">
                            CSV Roster File <span class="text-[#E07A5F]">*</span>
                        </label>
                        <div class="border-2 border-dashed border-[#F0EAE1] bg-[#FAF7F2] hover:bg-[#FAF7F2]/80 transition-colors rounded-2xl p-5 text-center cursor-pointer relative"
                             @click="$refs.fileInput.click()">
                            <input type="file" x-ref="fileInput" accept=".csv,.txt" class="hidden"
                                   @change="handleFileChange($event)">
                            <i class="bi bi-cloud-arrow-up text-3xl text-[#8E8E93]"></i>
                            <p class="text-xs font-bold text-[#1C1C1E] mt-2" x-text="fileName || 'Click to select CSV file'"></p>
                            <span class="text-[10px] text-[#8E8E93] block mt-0.5" x-text="fileSizeText || 'Max 10MB • Auto-detects columns'"></span>
                        </div>
                    </div>

                    <div class="p-3 bg-[#E8EAFA] rounded-2xl text-[11px] text-[#4C5270] leading-relaxed flex items-start gap-2">
                        <i class="bi bi-shield-check text-base shrink-0 mt-0.5"></i>
                        <div>
                            <strong>Cryptographic Audit:</strong> Uploading will trigger version freeze and record actor timestamp in SHA-256 audit log.
                        </div>
                    </div>

                    <button type="button" @click="startPreview()" :disabled="!hasFile || isReading"
                            class="w-full py-2.5 rounded-xl bg-[#F8A926] hover:bg-[#e09418] disabled:opacity-50 text-[#1C1C1E] text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5">
                        <i class="bi bi-magic" x-show="!isReading"></i>
                        <span x-show="!isReading">Preview & Map Columns</span>
                        <span x-show="isReading" class="flex items-center gap-1.5">
                            <span class="w-3.5 h-3.5 border-2 border-[#1C1C1E] border-t-transparent rounded-full animate-spin"></span>
                            Reading CSV...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Activity Picker Filter Card -->
            <div class="bg-white rounded-[24px] p-5 border border-[#F0EAE1] shadow-sm space-y-3">
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#8E8E93]">ACTIVITIES DIRECTORY</span>
                <div class="space-y-1.5">
                    @foreach($activities as $act)
                        <a href="{{ route('rosters.index', ['activity_id' => $act->id]) }}"
                           class="p-2.5 rounded-xl flex items-center justify-between text-xs transition-colors {{ $selectedActivity?->id === $act->id ? 'bg-[#FEEFD0] font-bold text-[#7A521E]' : 'hover:bg-[#FAF7F2] text-[#1C1C1E]' }}">
                            <div class="truncate max-w-[200px]">
                                {{ $act->title }}
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full {{ $selectedActivity?->id === $act->id ? 'bg-[#FDD97A] text-[#7A521E]' : 'bg-[#FAF7F2] text-[#8E8E93]' }}">
                                v{{ $act->activeRoster?->version ?? 1 }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right: Current Active Roster Table (FigCraft D5) -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-sm overflow-hidden p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#F0EAE1]">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-[#1C1C1E]">
                                {{ $selectedActivity?->title ?? 'Select an Activity' }}
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#EBF5EC] text-[#2E7D32]">
                                Active v{{ $selectedActivity?->activeRoster?->version ?? 1 }}
                            </span>
                        </div>
                        <p class="text-xs text-[#8E8E93] mt-0.5">
                            File: {{ $selectedActivity?->activeRoster?->filename ?? 'demo_roster.csv' }} •
                            {{ $selectedActivity?->activeRoster?->total_records ?? 0 }} Registered Participants
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-[#FAF7F2] text-[#8E8E93] font-bold uppercase tracking-wider text-[11px]">
                                <th class="py-3 px-4 rounded-l-xl">STUDENT NAME</th>
                                <th class="py-3 px-4">MATRIC NO</th>
                                <th class="py-3 px-4">PROGRAM</th>
                                <th class="py-3 px-4">IC VERIFICATION</th>
                                <th class="py-3 px-4 rounded-r-xl text-right">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#FAF7F2]">
                            @forelse($participants as $p)
                                <tr class="hover:bg-[#FAF7F2]/60 transition-colors">
                                    <td class="py-3.5 px-4 font-bold text-[#1C1C1E]">
                                        {{ $p->student_name }}
                                        <div class="text-[11px] font-normal text-[#8E8E93]">{{ $p->email ?? '—' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-semibold text-[#1C1C1E]">
                                        {{ $p->matric_no }}
                                    </td>
                                    <td class="py-3.5 px-4 text-[#8E8E93]">
                                        <span class="px-2 py-0.5 rounded bg-[#FAF7F2] border border-[#F0EAE1] font-semibold text-[#1C1C1E]">
                                            {{ $p->program_code }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-[#8E8E93]">
                                        ••••-••-{{ $p->ic_last_4 ?? '—' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#EBF5EC] text-[#2E7D32]">
                                            ● Eligible
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-[#8E8E93]">
                                        No participants loaded yet. Upload a CSV roster on the left.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($participants instanceof \Illuminate\Pagination\LengthAwarePaginator && $participants->hasPages())
                    <div class="pt-4 border-t border-[#F0EAE1]">
                        {{ $participants->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Enterprise Interactive Column Mapping & Data Preview Modal (Alpine.js) -->
    <div x-show="mappingModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs overflow-y-auto">
        <div @click.away="mappingModalOpen = false"
             class="bg-white rounded-[32px] border border-[#F0EAE1] shadow-2xl w-full max-w-4xl p-6 sm:p-8 space-y-6 my-8 max-h-[90vh] flex flex-col">

            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-[#F0EAE1]">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#FEEFD0] text-[#7A521E]">
                            Step 2: Interactive Column Mapper
                        </span>
                        <span class="text-xs text-[#8E8E93]" x-text="fileName"></span>
                    </div>
                    <h3 class="text-base font-bold text-[#1C1C1E] mt-1">Review CSV Structure & Map Data Fields</h3>
                    <p class="text-xs text-[#8E8E93]">
                        Map each column to its corresponding participant field in database. System auto-detected best matches.
                    </p>
                </div>
                <button type="button" @click="mappingModalOpen = false" class="text-[#8E8E93] hover:text-[#1C1C1E] text-lg p-1">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Scrollable Content Body -->
            <div class="overflow-y-auto space-y-6 flex-1 pr-1">
                <!-- Column Mapping Selector Grid -->
                <div class="bg-[#FAF7F2] rounded-2xl p-5 border border-[#F0EAE1] space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#1C1C1E] flex items-center gap-1.5">
                            <i class="bi bi-sliders text-[#F8A926]"></i> Column Assignments
                        </span>
                        <span class="text-[11px] text-[#8E8E93]" x-text="`${totalRows} participant rows detected`"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Student Name (Required) -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-[#1C1C1E] mb-1">
                                Full Name <span class="text-[#E07A5F]">*</span>
                            </label>
                            <select x-model="colName"
                                    class="w-full px-3 py-2 rounded-xl bg-white border border-[#F8A926] text-xs text-[#1C1C1E] font-medium focus:outline-none shadow-xs">
                                <option value="">-- Select Name Column --</option>
                                <template x-for="h in headers" :key="h.index">
                                    <option :value="h.index" x-text="`[Col ${h.index + 1}] ${h.label}`" :selected="colName == h.index"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Matric No (Required) -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-[#1C1C1E] mb-1">
                                Matric Number <span class="text-[#E07A5F]">*</span>
                            </label>
                            <select x-model="colMatric"
                                    class="w-full px-3 py-2 rounded-xl bg-white border border-[#F8A926] text-xs text-[#1C1C1E] font-medium focus:outline-none shadow-xs">
                                <option value="">-- Select Matric Column --</option>
                                <template x-for="h in headers" :key="h.index">
                                    <option :value="h.index" x-text="`[Col ${h.index + 1}] ${h.label}`" :selected="colMatric == h.index"></option>
                                </template>
                            </select>
                        </div>

                        <!-- IC / Last 4 Digits -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-[#1C1C1E] mb-1">
                                IC Number (Anti-Proxy)
                            </label>
                            <select x-model="colIc"
                                    class="w-full px-3 py-2 rounded-xl bg-white border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] shadow-xs">
                                <option value="">-- Skip / No IC Column --</option>
                                <template x-for="h in headers" :key="h.index">
                                    <option :value="h.index" x-text="`[Col ${h.index + 1}] ${h.label}`" :selected="colIc == h.index"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Email (Optional) -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-[#1C1C1E] mb-1">
                                Email Address (Optional)
                            </label>
                            <select x-model="colEmail"
                                    class="w-full px-3 py-2 rounded-xl bg-white border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] shadow-xs">
                                <option value="">-- Skip / No Email Column --</option>
                                <template x-for="h in headers" :key="h.index">
                                    <option :value="h.index" x-text="`[Col ${h.index + 1}] ${h.label}`" :selected="colEmail == h.index"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Program / Faculty (Optional) -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-[#1C1C1E] mb-1">
                                Program / Course Code (Optional)
                            </label>
                            <select x-model="colProgram"
                                    class="w-full px-3 py-2 rounded-xl bg-white border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] shadow-xs">
                                <option value="">-- Skip / Default to Faculty --</option>
                                <template x-for="h in headers" :key="h.index">
                                    <option :value="h.index" x-text="`[Col ${h.index + 1}] ${h.label}`" :selected="colProgram == h.index"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Interactive Data Preview Table -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#1C1C1E] flex items-center gap-1.5">
                            <i class="bi bi-table text-[#8E8E93]"></i> Data Sample (First 10 Rows)
                        </span>
                        <span class="text-[11px] text-[#2E7D32] font-semibold">● Live Preview Active</span>
                    </div>

                    <div class="border border-[#F0EAE1] rounded-2xl overflow-x-auto max-h-64 bg-white">
                        <table class="w-full text-left text-xs whitespace-nowrap">
                            <thead class="bg-[#FAF7F2] text-[#8E8E93] font-bold uppercase tracking-wider text-[10px] sticky top-0 border-b border-[#F0EAE1]">
                                <tr>
                                    <th class="py-2.5 px-3">#</th>
                                    <template x-for="h in headers" :key="h.index">
                                        <th class="py-2.5 px-3">
                                            <div x-text="h.label" class="font-bold text-[#1C1C1E]"></div>
                                            <div class="text-[9px] font-mono"
                                                 :class="{
                                                    'text-[#2E7D32] font-bold': colName == h.index || colMatric == h.index,
                                                    'text-[#8E8E93]': colName != h.index && colMatric != h.index
                                                 }"
                                                 x-text="getColumnRoleBadge(h.index)">
                                            </div>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#FAF7F2] font-mono text-[11px]">
                                <template x-for="(row, rIdx) in sampleRows" :key="rIdx">
                                    <tr class="hover:bg-[#FAF7F2]/50">
                                        <td class="py-2 px-3 text-[#8E8E93]" x-text="rIdx + 1"></td>
                                        <template x-for="(cell, cIdx) in row" :key="cIdx">
                                            <td class="py-2 px-3 text-[#1C1C1E]"
                                                :class="{
                                                    'bg-[#EBF5EC]/40 font-bold': colName == cIdx || colMatric == cIdx
                                                }"
                                                x-text="cell || '—'">
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Form Execution -->
            <form method="POST" action="{{ route('rosters.import') }}" class="pt-4 border-t border-[#F0EAE1] flex flex-col sm:flex-row items-center justify-between gap-3">
                @csrf
                <input type="hidden" name="activity_id" :value="activityId">
                <input type="hidden" name="raw_content" :value="rawContent">
                <input type="hidden" name="filename" :value="fileName">
                <input type="hidden" name="col_name" :value="colName">
                <input type="hidden" name="col_matric" :value="colMatric">
                <input type="hidden" name="col_ic" :value="colIc">
                <input type="hidden" name="col_email" :value="colEmail">
                <input type="hidden" name="col_program" :value="colProgram">

                <div class="text-[11px] text-[#8E8E93] self-start sm:self-auto">
                    <span x-show="colName === '' || colMatric === ''" class="text-[#E07A5F] font-bold">
                        ⚠️ Please map both Full Name and Matric Number before continuing.
                    </span>
                    <span x-show="colName !== '' && colMatric !== ''" class="text-[#2E7D32] font-semibold">
                        Ready to import <span x-text="totalRows"></span> students with DB transaction.
                    </span>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-auto">
                    <button type="button" @click="mappingModalOpen = false"
                            class="px-4 py-2.5 rounded-xl bg-[#FAF7F2] text-xs font-bold text-[#1C1C1E] border border-[#F0EAE1]">
                        Cancel
                    </button>
                    <button type="submit" :disabled="colName === '' || colMatric === ''"
                            class="px-6 py-2.5 rounded-xl bg-[#F8A926] hover:bg-[#e09418] disabled:opacity-50 text-[#1C1C1E] text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                        <i class="bi bi-check-circle-fill"></i> Execute Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function rosterImporter() {
    return {
        activityId: '{{ $selectedActivity?->id ?? $activities->first()?->id }}',
        hasFile: false,
        fileName: '',
        fileSizeText: '',
        isReading: false,
        mappingModalOpen: false,
        rawContent: '',
        headers: [],
        sampleRows: [],
        totalRows: 0,
        colName: '',
        colMatric: '',
        colIc: '',
        colEmail: '',
        colProgram: '',

        handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) {
                this.hasFile = false;
                this.fileName = '';
                this.fileSizeText = '';
                return;
            }
            this.hasFile = true;
            this.fileName = file.name;
            this.fileSizeText = (file.size / 1024).toFixed(1) + ' KB';
        },

        async startPreview() {
            const input = this.$refs.fileInput;
            if (!input.files || !input.files[0]) {
                alert('Please select a CSV file first.');
                return;
            }

            this.isReading = true;
            const formData = new FormData();
            formData.append('activity_id', this.activityId);
            formData.append('roster_file', input.files[0]);

            try {
                const response = await fetch('{{ route('rosters.preview') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    alert(data.message || 'Failed to read CSV file.');
                    this.isReading = false;
                    return;
                }

                this.headers = data.headers || [];
                this.sampleRows = data.sample_rows || [];
                this.totalRows = data.total_data_rows || 0;
                this.rawContent = data.raw_content || '';

                // Apply heuristics detected from server
                const d = data.detected || {};
                this.colName = d.name !== null && d.name !== undefined ? d.name : '';
                this.colMatric = d.matric_no !== null && d.matric_no !== undefined ? d.matric_no : '';
                this.colIc = d.ic_last_4 !== null && d.ic_last_4 !== undefined ? d.ic_last_4 : '';
                this.colEmail = d.email !== null && d.email !== undefined ? d.email : '';
                this.colProgram = d.program !== null && d.program !== undefined ? d.program : '';

                this.mappingModalOpen = true;
            } catch (err) {
                alert('Error connecting to server: ' + err.message);
            } finally {
                this.isReading = false;
            }
        },

        getColumnRoleBadge(index) {
            if (this.colName == index) return '→ NAME';
            if (this.colMatric == index) return '→ MATRIC';
            if (this.colIc == index) return '→ IC4';
            if (this.colEmail == index) return '→ EMAIL';
            if (this.colProgram == index) return '→ PROGRAM';
            return 'UNMAPPED';
        }
    }
}
</script>
@endsection
