@extends('layouts.app', ['title' => 'Notifications'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">Notifications</h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                {{ $summary['unread'] }} unread of {{ $summary['all'] }} total
            </p>
        </div>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit"
                    class="px-4 py-2 rounded-xl bg-white hover:bg-[#FAF7F2] text-xs font-bold text-[#1C1C1E] border border-[#F0EAE1] shadow-sm flex items-center gap-1.5">
                <i class="bi bi-check2-all"></i> Mark all as read
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 rounded-xl bg-[#EBF5EC] border border-[#2E7D32]/20 text-xs font-semibold text-[#2E7D32]">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[300px_minmax(0,1fr)] gap-6 items-start">
    <!-- Filter chips + search -->
    <div class="bg-white rounded-[24px] border border-[#F0EAE1] shadow-sm p-4 space-y-3 lg:sticky lg:top-[94px]">
        <div class="flex flex-wrap lg:flex-col lg:items-stretch gap-2">
            @foreach([
                'all' => ['All', $summary['all']],
                'unread' => ['Unread', $summary['unread']],
                'important' => ['Important', $summary['important']],
                'roster' => ['Roster', null],
                'session' => ['Session', null],
                'record' => ['Record', null],
            ] as $key => $tab)
                <a href="{{ route('notifications.index', array_filter(['filter' => $key, 'search' => $search ?: null])) }}"
                   class="px-3 py-1.5 rounded-full text-[11px] font-bold border transition-colors flex items-center gap-1.5
                   {{ $filter === $key ? 'bg-[#F8A926] text-[#1C1C1E] border-[#F8A926]' : 'bg-[#FAF7F2] text-[#8E8E93] border-[#F0EAE1] hover:text-[#1C1C1E]' }}">
                    {{ $tab[0] }}
                    @if($tab[1] !== null)
                        <span class="px-1.5 rounded-full bg-white/70 text-[10px]">{{ $tab[1] }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('notifications.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="search" name="search" value="{{ $search }}"
                   placeholder="Search notifications..."
                   class="flex-1 px-3.5 py-2 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white">
            <button type="submit" class="px-3.5 py-2 rounded-xl bg-[#FAF7F2] hover:bg-[#F0EAE1] border border-[#F0EAE1] text-xs font-bold text-[#1C1C1E]">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>

    <!-- List -->
    <div class="space-y-6 min-w-0">
    <div class="bg-white rounded-[24px] border border-[#F0EAE1] shadow-sm divide-y divide-[#FAF7F2] overflow-hidden">
        @forelse($notifications as $n)
            @php
                $typeMeta = [
                    'roster' => ['bi-file-earmark-spreadsheet', 'bg-[#E8EAFA] text-[#4C5270]'],
                    'session' => ['bi-broadcast', 'bg-[#FEEFD0] text-[#7A521E]'],
                    'record' => ['bi-clipboard-check', 'bg-[#E5F0E6] text-[#2E7D32]'],
                    'activity' => ['bi-lightning-charge', 'bg-[#FDE8E1] text-[#E07A5F]'],
                    'system' => ['bi-gear', 'bg-[#FAF7F2] text-[#8E8E93]'],
                ][$n->type] ?? ['bi-bell', 'bg-[#FAF7F2] text-[#8E8E93]'];
                $isUrgent = in_array($n->priority, ['high', 'urgent'], true);
            @endphp
            <div class="flex items-start gap-3 p-4 {{ $n->isRead() ? '' : 'bg-[#FEEFD0]/20' }}">
                <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $typeMeta[1] }}">
                    <i class="bi {{ $typeMeta[0] }}"></i>
                </span>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('notifications.show', $n) }}"
                           class="text-xs font-bold text-[#1C1C1E] hover:text-[#F8A926] {{ $n->isRead() ? '' : 'font-extrabold' }}">
                            {{ $n->title }}
                        </a>
                        @if($isUrgent)
                            <span class="px-1.5 py-0.5 rounded-full bg-[#FDE8E1] text-[#E07A5F] text-[10px] font-bold uppercase">
                                {{ $n->priority }}
                            </span>
                        @endif
                        @unless($n->isRead())
                            <span class="w-1.5 h-1.5 rounded-full bg-[#F8A926]" title="Unread"></span>
                        @endunless
                    </div>

                    @if($n->message)
                        <p class="text-[11px] text-[#8E8E93] mt-0.5 leading-relaxed">{{ $n->message }}</p>
                    @endif

                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="text-[10px] text-[#8E8E93]">{{ $n->created_at->diffForHumans() }}</span>
                        <span class="text-[10px] uppercase font-bold text-[#8E8E93]">{{ $n->type }}</span>
                        @unless($n->isRead())
                            <form method="POST" action="{{ route('notifications.read', $n) }}">
                                @csrf
                                <button type="submit" class="text-[10px] font-bold text-[#F8A926] hover:underline">Mark read</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('notifications.destroy', $n) }}"
                              onsubmit="return confirm('Remove this notification?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[10px] font-bold text-[#E07A5F] hover:underline">Remove</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-10 text-center space-y-2">
                <i class="bi bi-bell-slash text-3xl text-[#8E8E93]"></i>
                <p class="text-xs text-[#8E8E93]">No notifications match this filter.</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div>{{ $notifications->links() }}</div>
    @endif
    </div>
    </div>
</div>
@endsection
