@extends('layouts.app', ['title' => 'Notification'])

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-[#8E8E93] hover:text-[#1C1C1E] inline-flex items-center gap-1">
            <i class="bi bi-arrow-left"></i> Back to Notifications
        </a>
        <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E] mt-2">{{ $notification->title }}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_300px] gap-6 items-start">
        <div class="bg-white rounded-[24px] border border-[#F0EAE1] shadow-sm p-6 space-y-4 min-w-0">
            @if($notification->message)
                <p class="text-sm text-[#1C1C1E] leading-relaxed whitespace-pre-line">{{ $notification->message }}</p>
            @else
                <p class="text-xs text-[#8E8E93]">This notification has no further details.</p>
            @endif

            <div class="pt-4 border-t border-[#F0EAE1] flex items-center justify-between text-[11px] text-[#8E8E93]">
                <span>{{ $notification->created_at->format('d M Y, h:i A') }}</span>
                <span>{{ $notification->isRead() ? 'Read' : 'Unread' }}</span>
            </div>
        </div>

        <div class="bg-white rounded-[24px] border border-[#F0EAE1] shadow-sm p-5 space-y-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-[#8E8E93]">Details</p>
            <div class="flex items-center justify-between text-xs">
                <span class="text-[#8E8E93]">Type</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-[#FAF7F2] text-[#1C1C1E]">{{ $notification->type }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-[#8E8E93]">Priority</span>
                <span class="font-bold text-[#1C1C1E] capitalize">{{ $notification->priority }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-[#8E8E93]">Received</span>
                <span class="font-bold text-[#1C1C1E]">{{ $notification->created_at->diffForHumans() }}</span>
            </div>

            <form method="POST" action="{{ route('notifications.destroy', $notification) }}"
                  onsubmit="return confirm('Remove this notification?')" class="pt-3 border-t border-[#F0EAE1]">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full px-3 py-2 rounded-xl bg-[#FDE8E1] hover:bg-[#FCD5C8] text-[11px] font-bold text-[#E07A5F] transition-colors">
                    Remove notification
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
