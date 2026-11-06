@extends('layouts.app', ['title' => $activity->exists ? 'Edit Activity' : 'New Activity'])

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('activities.index') }}" class="text-xs font-bold text-[#8E8E93] hover:text-[#1C1C1E] inline-flex items-center gap-1">
            <i class="bi bi-arrow-left"></i> Back to Activities
        </a>
        <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E] mt-2">
            {{ $activity->exists ? 'Edit Activity' : 'Create New Activity' }}
        </h1>
        <p class="text-xs text-[#8E8E93] mt-0.5">
            An activity groups one event's roster and its attendance sessions.
        </p>
    </div>

    <form method="POST"
          action="{{ $activity->exists ? route('activities.update', $activity) : route('activities.store') }}"
          class="bg-white rounded-[28px] p-6 sm:p-8 border border-[#F0EAE1] shadow-sm grid grid-cols-1 lg:grid-cols-2 gap-5">
        @csrf
        @if($activity->exists)
            @method('PUT')
        @endif

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-2">Activity Title</label>
            <input type="text" name="title" required maxlength="255"
                   value="{{ old('title', $activity->title) }}"
                   placeholder="e.g. Majlis Amanat Dekan FTMK 2026"
                   class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-sm text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
            @error('title')
                <p class="mt-1 text-xs text-[#E07A5F]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-2">Faculty / Organiser Unit</label>
            <input type="text" name="faculty" maxlength="100"
                   value="{{ old('faculty', $activity->faculty ?? auth()->user()->faculty) }}"
                   placeholder="e.g. FTMK"
                   class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-sm text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
            @error('faculty')
                <p class="mt-1 text-xs text-[#E07A5F]">{{ $message }}</p>
            @enderror
        </div>

        <div class="lg:col-span-2">
            <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-2">Description <span class="text-[#8E8E93] font-normal normal-case">(optional)</span></label>
            <textarea name="description" rows="3" maxlength="2000"
                      placeholder="Short note about this activity"
                      class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-sm text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all resize-none">{{ old('description', $activity->description) }}</textarea>
            @error('description')
                <p class="mt-1 text-xs text-[#E07A5F]">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2 pt-2 lg:col-span-2">
            <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold shadow-sm transition-all">
                {{ $activity->exists ? 'Save Changes' : 'Create Activity' }}
            </button>
            <a href="{{ route('activities.index') }}"
               class="px-5 py-2.5 rounded-xl bg-[#FAF7F2] hover:bg-[#F0EAE1] text-[#1C1C1E] text-xs font-bold border border-[#F0EAE1] transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
