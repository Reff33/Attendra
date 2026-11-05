<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#FAF7F2]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Attendra' }} — Activity & Attendance Management</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #FAF7F2;
            color: #1C1C1E;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-full flex flex-col bg-[#FAF7F2] antialiased">
    <!-- Top Navbar (FigCraft D3-D11 Standardized) -->
    <header class="h-[74px] bg-white border-b border-[#F0EAE1] sticky top-0 z-40 px-6 sm:px-10 flex items-center justify-between">
        <!-- Left: Brand Logo -->
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-[22px] font-bold tracking-tight text-[#1C1C1E] flex items-center">
                Attendra<span class="text-[#F8A926]">.</span>
            </a>
            @if(auth()->user()->isAdmin())
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#FEEFD0] text-[#7A521E] border border-[#F8A926]/40 flex items-center gap-1">
                    👑 ADMIN
                </span>
            @endif
        </div>

        <!-- Center: Standardized Auto-Layout Pill Menu -->
        <nav class="hidden md:flex items-center bg-[#FAF7F2] border border-[#F0EAE1] rounded-full p-1 gap-1">
            @if(request()->is('admin*'))
                <!-- Admin Tabs -->
                <a href="{{ route('admin.dashboard') }}"
                   class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-white text-[#1C1C1E] shadow-sm border border-[#EAE6DF] font-bold' : 'text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                    Overview
                </a>
                <a href="{{ route('admin.staff.index') }}"
                   class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ request()->routeIs('admin.staff*') ? 'bg-white text-[#1C1C1E] shadow-sm border border-[#EAE6DF] font-bold' : 'text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                    Staff Directory
                </a>
                <a href="{{ route('admin.activities.index') }}"
                   class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ request()->routeIs('admin.activities*') ? 'bg-white text-[#1C1C1E] shadow-sm border border-[#EAE6DF] font-bold' : 'text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                    All Activities
                </a>
                <a href="{{ route('admin.audit.index') }}"
                   class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ request()->routeIs('admin.audit*') ? 'bg-white text-[#1C1C1E] shadow-sm border border-[#EAE6DF] font-bold' : 'text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                    Governance
                </a>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="px-4 py-2 rounded-full text-xs font-semibold text-[#8E8E93] hover:text-[#1C1C1E] flex items-center gap-1">
                        Operations ▾
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-[#F0EAE1] py-2 z-50">
                        <a href="{{ route('quick-ops.scanner') }}" class="block px-4 py-2 text-xs font-medium text-[#1C1C1E] hover:bg-[#FAF7F2]">Staff Camera Scanner</a>
                        <a href="{{ route('quick-ops.walk-in') }}" class="block px-4 py-2 text-xs font-medium text-[#1C1C1E] hover:bg-[#FAF7F2]">Manual Walk-In Entry</a>
                        <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-xs font-bold text-[#F8A926] hover:bg-[#FAF7F2]">⇄ Switch to Organizer Mode</a>
                    </div>
                </div>
            @else
                <!-- Staff Organizer Tabs -->
                <a href="{{ route('dashboard') }}"
                   class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-white text-[#1C1C1E] shadow-sm border border-[#EAE6DF] font-bold' : 'text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                    Dashboard
                </a>
                <a href="{{ route('sessions.index') }}"
                   class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ request()->routeIs('sessions.*') ? 'bg-white text-[#1C1C1E] shadow-sm border border-[#EAE6DF] font-bold' : 'text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                    Sessions
                </a>
                <a href="{{ route('rosters.index') }}"
                   class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ request()->routeIs('rosters.*') ? 'bg-white text-[#1C1C1E] shadow-sm border border-[#EAE6DF] font-bold' : 'text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                    Datasets
                </a>
                <a href="{{ route('matrix.index') }}"
                   class="px-4 py-2 rounded-full text-xs font-semibold transition-colors {{ request()->routeIs('matrix.*') ? 'bg-white text-[#1C1C1E] shadow-sm border border-[#EAE6DF] font-bold' : 'text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                    Reports
                </a>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open"
                            class="px-4 py-2 rounded-full text-xs font-semibold transition-colors flex items-center gap-1 {{ request()->is('operations*') || request()->is('audit*') ? 'bg-white text-[#1C1C1E] shadow-sm border border-[#EAE6DF] font-bold' : 'text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        More ▾
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-xl border border-[#F0EAE1] py-2 z-50">
                        <a href="{{ route('quick-ops.scanner') }}" class="block px-4 py-2 text-xs font-medium text-[#1C1C1E] hover:bg-[#FAF7F2] flex items-center gap-2">
                            <i class="bi bi-camera-fill text-[#8E8E93]"></i> Quick Scan Pass
                        </a>
                        <a href="{{ route('quick-ops.walk-in') }}" class="block px-4 py-2 text-xs font-medium text-[#1C1C1E] hover:bg-[#FAF7F2] flex items-center gap-2">
                            <i class="bi bi-person-plus-fill text-[#8E8E93]"></i> Manual Walk-In Form
                        </a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.audit.index') }}" class="block px-4 py-2 text-xs font-medium text-[#1C1C1E] hover:bg-[#FAF7F2] flex items-center gap-2">
                                <i class="bi bi-shield-check text-[#8E8E93]"></i> Audit Trail & Governance
                            </a>
                            <div class="border-t border-[#F0EAE1] my-1"></div>
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-xs font-bold text-[#F8A926] hover:bg-[#FAF7F2] flex items-center gap-2">
                                <i class="bi bi-shield-lock-fill text-[#F8A926]"></i> Admin Console
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </nav>

        <!-- Right Utilities & Profile Avatar -->
        <div class="flex items-center gap-3">
            @if(auth()->user()->isAdmin())
                @if(request()->is('admin*'))
                    <a href="{{ route('dashboard') }}" class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-[#FAF7F2] hover:bg-[#F0EAE1] text-[#1C1C1E] border border-[#EAE6DF] transition-colors">
                        <i class="bi bi-arrow-repeat text-[#F8A926]"></i> Organizer Mode
                    </a>
                @else
                    <a href="{{ route('admin.dashboard') }}" class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-[#FEEFD0] hover:bg-[#FDD97A] text-[#7A521E] border border-[#F8A926]/50 transition-colors">
                        <i class="bi bi-shield-lock-fill text-[#F8A926]"></i> Admin Console
                    </a>
                @endif
            @endif

            <button class="w-[38px] h-[38px] rounded-full bg-[#FAF7F2] border border-[#F0EAE1] flex items-center justify-center text-[#1C1C1E] hover:bg-[#F0EAE1] transition-colors">
                <i class="bi bi-search text-sm"></i>
            </button>
            <button class="w-[38px] h-[38px] rounded-full bg-[#FAF7F2] border border-[#F0EAE1] flex items-center justify-center text-[#1C1C1E] hover:bg-[#F0EAE1] transition-colors">
                <i class="bi bi-bell text-sm"></i>
            </button>

            <!-- User Menu -->
            <div x-data="{ userOpen: false }" class="relative">
                <button @click="userOpen = !userOpen" class="h-[38px] px-3 rounded-full bg-[#FEEFD0] border border-[#F8A926] flex items-center gap-2 text-xs font-bold text-[#7A521E] hover:bg-[#FDD97A] transition-colors">
                    <span>{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#2E7D32]"></span>
                </button>
                <div x-show="userOpen" @click.away="userOpen = false" x-cloak class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-[#F0EAE1] p-3 z-50">
                    <div class="px-2 py-1.5 border-b border-[#F0EAE1] mb-2">
                        <p class="text-xs font-bold text-[#1C1C1E] truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] text-[#8E8E93] truncate">{{ auth()->user()->faculty ?? auth()->user()->email }}</p>
                        <span class="inline-block mt-1 text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-[#FAF7F2] text-[#F8A926]">
                            {{ auth()->user()->staff_id ?? auth()->user()->role }}
                        </span>
                    </div>

                    <a href="{{ route('profile.edit') }}" class="w-full text-left px-2.5 py-2 text-xs font-semibold text-[#1C1C1E] hover:bg-[#FAF7F2] rounded-xl flex items-center gap-2 transition-colors mb-1 {{ request()->routeIs('profile.*') ? 'bg-[#FAF7F2] font-bold text-[#F8A926]' : '' }}">
                        <i class="bi bi-person-circle text-[#F8A926] text-sm"></i>
                        <span>My Profile & Account</span>
                    </a>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.audit.index') }}" class="w-full text-left px-2.5 py-2 text-xs font-semibold text-[#1C1C1E] hover:bg-[#FAF7F2] rounded-xl flex items-center gap-2 transition-colors mb-2">
                            <i class="bi bi-shield-check text-[#8E8E93] text-sm"></i>
                            <span>Audit Log</span>
                        </a>
                    @endif

                    <div class="border-t border-[#F0EAE1] pt-1.5">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-2.5 py-1.5 text-xs font-semibold text-[#E07A5F] hover:bg-[#FDE8E1] rounded-xl flex items-center gap-2 transition-colors">
                                <i class="bi bi-box-arrow-right"></i> Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Workspace with Slim Vertical Floating Dock -->
    <div class="flex-1 flex w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 gap-6">
        <!-- Standardized Left Slim Dock (68px wide) (FigCraft D3-D11) -->
        <aside class="hidden lg:flex flex-col items-center justify-between w-[68px] min-h-[calc(100vh-120px)] bg-white rounded-[32px] border border-[#F0EAE1] shadow-[0_4px_16px_rgba(0,0,0,0.03)] py-6 shrink-0 sticky top-[94px]">
            <!-- Top Nav Slots -->
            <div class="flex flex-col items-center gap-4">
                @if(request()->is('admin*'))
                    <!-- Admin Dock Buttons -->
                    <a href="{{ route('admin.dashboard') }}" title="Admin Overview"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-bank text-lg"></i>
                    </a>
                    <a href="{{ route('admin.staff.index') }}" title="Staff Directory"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->routeIs('admin.staff*') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-people text-lg"></i>
                    </a>
                    <a href="{{ route('admin.activities.index') }}" title="All Activities"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->routeIs('admin.activities*') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-lightning-charge text-lg"></i>
                    </a>
                    <a href="{{ route('admin.audit.index') }}" title="Governance Audit"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->routeIs('admin.audit*') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-shield-check text-lg"></i>
                    </a>
                    <a href="{{ route('quick-ops.scanner') }}" title="Operations"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->is('operations*') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-qr-code-scan text-lg"></i>
                    </a>
                @else
                    <!-- Staff Dock Buttons -->
                    <a href="{{ route('dashboard') }}" title="Organizer Dashboard"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-house-door text-lg"></i>
                    </a>
                    <a href="{{ route('sessions.index') }}" title="Sessions Management"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->routeIs('sessions.*') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-compass text-lg"></i>
                    </a>
                    <a href="{{ route('rosters.index') }}" title="Roster & Datasets"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->routeIs('rosters.*') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-file-earmark-spreadsheet text-lg"></i>
                    </a>
                    <a href="{{ route('matrix.index') }}" title="Attendance Matrix"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->routeIs('matrix.*') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-grid-3x3-gap text-lg"></i>
                    </a>
                    <a href="{{ route('quick-ops.scanner') }}" title="Quick Operations"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->is('operations*') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-lightning-charge text-lg"></i>
                    </a>
                @endif
            </div>

            <!-- Bottom Utility Slots (Profile, Settings & Logout) -->
            <div class="flex flex-col items-center gap-3">
                <a href="{{ route('profile.edit') }}" title="My Profile"
                   class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->routeIs('profile.*') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                    <i class="bi bi-person text-lg"></i>
                </a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.audit.index') }}" title="Audit Logs"
                       class="w-11 h-11 rounded-[14px] flex items-center justify-center transition-colors {{ request()->routeIs('admin.audit*') ? 'bg-[#F8A926] text-white shadow-md' : 'bg-[#FAF7F2] text-[#8E8E93] hover:text-[#1C1C1E]' }}">
                        <i class="bi bi-shield-check text-lg"></i>
                    </a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sign Out"
                            class="w-11 h-11 rounded-[14px] flex items-center justify-center bg-[#FDE8E1] text-[#E07A5F] hover:bg-[#FCD5C8] transition-colors">
                        <i class="bi bi-box-arrow-right text-lg"></i>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Dynamic Content Canvas -->
        <main class="flex-1 min-w-0 pb-20 lg:pb-0">
            @if(session('success'))
                <div class="mb-5 p-4 rounded-2xl bg-[#EBF5EC] border border-[#2E7D32]/20 text-[#2E7D32] text-sm font-semibold flex items-center gap-3">
                    <i class="bi bi-check-circle-fill text-base"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 p-4 rounded-2xl bg-[#FDE8E1] border border-[#E07A5F]/20 text-[#E07A5F] text-sm font-semibold flex items-center gap-3">
                    <i class="bi bi-exclamation-triangle-fill text-base"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Mobile Floating Bottom Pill Navigation Bar (FigCraft Mobile Spec: 35px radius) -->
    <nav class="lg:hidden fixed bottom-4 left-4 right-4 z-50 bg-white/95 backdrop-blur-md rounded-[35px] border border-[#F0EAE1] shadow-[0_8px_30px_rgba(0,0,0,0.08)] px-5 py-2.5 flex items-center justify-between">
        @if(request()->is('admin*'))
            <a href="{{ route('admin.dashboard') }}"
               class="flex flex-col items-center gap-0.5 text-[10px] font-bold {{ request()->routeIs('admin.dashboard') ? 'text-[#F8A926]' : 'text-[#8E8E93]' }}">
                <i class="bi bi-bank text-lg"></i>
                <span>Home</span>
            </a>
            <a href="{{ route('admin.staff.index') }}"
               class="flex flex-col items-center gap-0.5 text-[10px] font-bold {{ request()->routeIs('admin.staff*') ? 'text-[#F8A926]' : 'text-[#8E8E93]' }}">
                <i class="bi bi-people text-lg"></i>
                <span>Staff</span>
            </a>
            <!-- Center Action CTA (+) -->
            <a href="{{ route('sessions.create') }}"
               class="w-11 h-11 -mt-5 rounded-full bg-[#F8A926] text-[#1C1C1E] shadow-lg flex items-center justify-center text-xl font-bold hover:scale-105 transition-transform">
                <i class="bi bi-plus-lg"></i>
            </a>
            <a href="{{ route('admin.activities.index') }}"
               class="flex flex-col items-center gap-0.5 text-[10px] font-bold {{ request()->routeIs('admin.activities*') ? 'text-[#F8A926]' : 'text-[#8E8E93]' }}">
                <i class="bi bi-lightning-charge text-lg"></i>
                <span>Events</span>
            </a>
            <a href="{{ route('dashboard') }}"
               class="flex flex-col items-center gap-0.5 text-[10px] font-bold text-[#8E8E93]">
                <i class="bi bi-person text-lg"></i>
                <span>Staff UI</span>
            </a>
        @else
            <a href="{{ route('dashboard') }}"
               class="flex flex-col items-center gap-0.5 text-[10px] font-bold {{ request()->routeIs('dashboard') ? 'text-[#F8A926]' : 'text-[#8E8E93]' }}">
                <i class="bi bi-house-door text-lg"></i>
                <span>Home</span>
            </a>
            <a href="{{ route('sessions.index') }}"
               class="flex flex-col items-center gap-0.5 text-[10px] font-bold {{ request()->routeIs('sessions*') ? 'text-[#F8A926]' : 'text-[#8E8E93]' }}">
                <i class="bi bi-compass text-lg"></i>
                <span>Sessions</span>
            </a>
            <!-- Center Action CTA (+) -->
            <a href="{{ route('sessions.create') }}"
               class="w-11 h-11 -mt-5 rounded-full bg-[#F8A926] text-[#1C1C1E] shadow-lg flex items-center justify-center text-xl font-bold hover:scale-105 transition-transform">
                <i class="bi bi-plus-lg"></i>
            </a>
            <a href="{{ route('rosters.index') }}"
               class="flex flex-col items-center gap-0.5 text-[10px] font-bold {{ request()->routeIs('rosters*') ? 'text-[#F8A926]' : 'text-[#8E8E93]' }}">
                <i class="bi bi-file-earmark-spreadsheet text-lg"></i>
                <span>Roster</span>
            </a>
            <a href="{{ route('quick-ops.scanner') }}"
               class="flex flex-col items-center gap-0.5 text-[10px] font-bold {{ request()->is('quick-ops*') ? 'text-[#F8A926]' : 'text-[#8E8E93]' }}">
                <i class="bi bi-qr-code-scan text-lg"></i>
                <span>Scan</span>
            </a>
        @endif
    </nav>
    @stack('scripts')
</body>
</html>
