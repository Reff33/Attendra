@extends('layouts.app', ['title' => 'Create Attendance Session'])

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    .leaflet-container {
        font-family: 'Inter', sans-serif;
        border-radius: 1rem;
        z-index: 10;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('sessions.index') }}" class="text-xs font-bold text-[#8E8E93] hover:text-[#1C1C1E] flex items-center gap-1 mb-1">
                <i class="bi bi-arrow-left"></i> Back to Sessions
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-[#1C1C1E]">
                Create New Attendance Session
            </h1>
            <p class="text-xs text-[#8E8E93] mt-0.5">
                Define session venue, timeframe, and anti-proxy verification policies
            </p>
        </div>
    </div>

    <!-- Session Form Card -->
    <div class="bg-white rounded-[28px] border border-[#F0EAE1] shadow-sm p-6 sm:p-8"
         x-data="sessionForm()">
        <form method="POST" action="{{ route('sessions.store') }}" class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            @csrf

            <div class="space-y-6 min-w-0">
            <!-- Activity Target -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-2">
                    Parent Activity
                </label>
                <select name="activity_id" required
                        class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                    @foreach($activities as $act)
                        <option value="{{ $act->id }}">{{ $act->title }} ({{ $act->faculty }})</option>
                    @endforeach
                </select>
                @error('activity_id')
                    <p class="mt-1 text-xs text-[#E07A5F]">{{ $message }}</p>
                @enderror
                <p class="text-[10px] text-[#8E8E93] mt-1">
                    Attendance is checked against this activity's active roster.
                    <a href="{{ route('rosters.index') }}" class="font-bold text-[#F8A926] hover:underline">Upload roster</a>
                </p>
            </div>

            <!-- Session Title & Venue -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-2">
                        Session Title / Sub-Event
                    </label>
                    <input type="text" name="title" required
                           placeholder="e.g. Morning Keynote: Dean Address FTMK"
                           class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                    @error('title')
                        <p class="mt-1 text-xs text-[#E07A5F]">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-2">
                        Venue / Location
                    </label>
                    <input type="text" name="venue" x-model="venue" required
                           placeholder="e.g. Dewan Al-Jazari, FTMK"
                           class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                    @error('venue')
                        <p class="mt-1 text-xs text-[#E07A5F]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Verification Policy Selection (PRD §2.1 V01 / Anti-Proxy) -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-2">
                    Anti-Proxy Verification Policy
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="cursor-pointer border rounded-2xl p-4 transition-all"
                           :class="mode === 'ic_and_gps' ? 'border-[#F8A926] bg-[#FEEFD0]/30 shadow-sm' : 'border-[#F0EAE1] bg-[#FAF7F2]'">
                        <input type="radio" name="verification_mode" value="ic_and_gps" x-model="mode" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-[#1C1C1E]">4-Digit IC + GPS</span>
                            <i class="bi bi-shield-check text-[#F8A926]"></i>
                        </div>
                        <p class="text-[11px] text-[#8E8E93] leading-relaxed">
                            Maximum security. Validates last 4 digits against roster & ensures student is within venue radius.
                        </p>
                    </label>

                    <label class="cursor-pointer border rounded-2xl p-4 transition-all"
                           :class="mode === 'ic_last_4' ? 'border-[#F8A926] bg-[#FEEFD0]/30 shadow-sm' : 'border-[#F0EAE1] bg-[#FAF7F2]'">
                        <input type="radio" name="verification_mode" value="ic_last_4" x-model="mode" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-[#1C1C1E]">4-Digit IC Only</span>
                            <i class="bi bi-credit-card-2-front text-[#8E8E93]"></i>
                        </div>
                        <p class="text-[11px] text-[#8E8E93] leading-relaxed">
                            Lightweight identity check. Matches student identity without requiring mobile device location permissions.
                        </p>
                    </label>

                    <label class="cursor-pointer border rounded-2xl p-4 transition-all"
                           :class="mode === 'gps' ? 'border-[#F8A926] bg-[#FEEFD0]/30 shadow-sm' : 'border-[#F0EAE1] bg-[#FAF7F2]'">
                        <input type="radio" name="verification_mode" value="gps" x-model="mode" class="sr-only">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold text-[#1C1C1E]">GPS Radar Only</span>
                            <i class="bi bi-geo-alt text-[#8E8E93]"></i>
                        </div>
                        <p class="text-[11px] text-[#8E8E93] leading-relaxed">
                            Presence check via device location. Fast check-in suitable for open hall events.
                        </p>
                    </label>
                </div>
            </div>

            <!-- Geofence Radius Slider (conditional if GPS enabled) -->
            <div x-show="mode === 'gps' || mode === 'ic_and_gps'" x-transition class="p-4 rounded-2xl bg-[#FAF7F2] border border-[#F0EAE1] space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-[#1C1C1E]">
                        Geofence Radius (Meters from Venue)
                    </label>
                    <span class="text-xs font-mono font-bold text-[#F8A926]" x-text="radius + 'm'">100m</span>
                </div>
                <input type="range" name="geofence_radius_meters" min="20" max="500" step="10"
                       x-model="radius"
                       @input="updateRadius($event.target.value)"
                       class="w-full accent-[#F8A926] cursor-pointer">
                <p class="text-[11px] text-[#8E8E93]">
                    Recommended: 50m for lecture halls, 100m for Dewan Al-Jazari / Chancellor Hall.
                </p>
            </div>

            </div><!-- /left column -->

            <div class="space-y-6 min-w-0 lg:sticky lg:top-[94px]">
            <!-- Interactive Leaflet Map Picker (OpenStreetMap Geofence) -->
            <div x-show="mode === 'gps' || mode === 'ic_and_gps'" x-transition class="space-y-3 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E]">
                            Interactive Venue Pin & Geofence Boundary
                        </label>
                        <p class="text-[11px] text-[#8E8E93]">
                            Drag the marker on the map to set the exact coordinates for the attendance center point.
                        </p>
                    </div>
                    <button type="button" @click="detectCurrentLocation()"
                            class="px-3.5 py-1.5 rounded-full bg-white border border-[#F0EAE1] hover:bg-[#FAF7F2] text-[11px] font-bold text-[#1C1C1E] shadow-2xs flex items-center gap-1.5 transition-colors shrink-0">
                        <i class="bi bi-crosshair text-[#F8A926]"></i>
                        <span>🎯 Use Current Location</span>
                    </button>
                </div>

                <!-- Leaflet Map Canvas Box -->
                <div class="relative rounded-2xl overflow-hidden border border-[#F0EAE1] shadow-2xs bg-[#FAF7F2]">
                    <div id="venueMap" class="w-full h-80 z-10" style="min-height: 320px;"></div>

                    <!-- Map Overlay Notification Banner -->
                    <div class="absolute bottom-3 left-3 z-[400] bg-white/95 backdrop-blur-xs px-3.5 py-1.5 rounded-xl border border-[#F0EAE1] text-[11px] font-medium text-[#1C1C1E] shadow-sm flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#F8A926] animate-pulse"></span>
                        <span>Hover map to scroll-zoom &middot; drag marker to reposition</span>
                    </div>
                </div>

                <!-- Coordinate & Geofence Status Chips -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-[11px]">
                    <div class="p-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] flex items-center justify-between">
                        <span class="text-[#8E8E93]">Latitude:</span>
                        <span class="font-mono font-bold text-[#1C1C1E]" x-text="lat ? lat.toFixed(6) : '2.313889'"></span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] flex items-center justify-between">
                        <span class="text-[#8E8E93]">Longitude:</span>
                        <span class="font-mono font-bold text-[#1C1C1E]" x-text="lng ? lng.toFixed(6) : '102.319444'"></span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-[#FEEFD0] border border-[#F8A926]/40 flex items-center justify-between">
                        <span class="text-[#7A521E] font-medium">Zone Radius:</span>
                        <span class="font-mono font-extrabold text-[#7A521E]" x-text="radius + ' meters'"></span>
                    </div>
                </div>

                <!-- Hidden Real GPS Inputs to Controller -->
                <input type="hidden" name="latitude" :value="lat">
                <input type="hidden" name="longitude" :value="lng">
            </div>

            <!-- Schedule Window -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-2">
                        Start Time
                    </label>
                    <input type="datetime-local" name="starts_at"
                           value="{{ now()->format('Y-m-d\TH:i') }}"
                           class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#1C1C1E] mb-2">
                        End Time
                    </label>
                    <input type="datetime-local" name="ends_at"
                           value="{{ now()->addHours(3)->format('Y-m-d\TH:i') }}"
                           class="w-full px-4 py-2.5 rounded-xl bg-[#FAF7F2] border border-[#F0EAE1] text-xs text-[#1C1C1E] focus:outline-none focus:border-[#F8A926] focus:bg-white transition-all">
                </div>
            </div>
            </div><!-- /right column -->

            <!-- Submit Button -->
            <div class="pt-4 border-t border-[#F0EAE1] flex items-center justify-end gap-3 lg:col-span-2">
                <a href="{{ route('sessions.index') }}"
                   class="px-5 py-2.5 rounded-xl bg-[#FAF7F2] hover:bg-[#F0EAE1] text-xs font-bold text-[#1C1C1E] border border-[#F0EAE1] transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-[#F8A926] hover:bg-[#e09418] text-[#1C1C1E] text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                    <i class="bi bi-broadcast"></i> Save & Broadcast Session
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    function sessionForm() {
        return {
            mode: 'ic_and_gps',
            venue: 'Dewan Al-Jazari, FTMK',
            lat: 2.313889,
            lng: 102.319444,
            radius: 100,
            map: null,
            marker: null,
            circle: null,

            init() {
                this.$watch('mode', (newMode) => {
                    if (newMode === 'gps' || newMode === 'ic_and_gps') {
                        setTimeout(() => {
                            this.initOrRefreshMap();
                        }, 100);
                    }
                });

                // Init after DOM is ready
                setTimeout(() => {
                    this.initOrRefreshMap();
                }, 200);
            },

            initOrRefreshMap() {
                const container = document.getElementById('venueMap');
                if (!container || typeof L === 'undefined') return;

                if (!this.map) {
                    this.map = L.map('venueMap', {
                        center: [this.lat, this.lng],
                        zoom: 17,
                        minZoom: 3,
                        maxZoom: 19,
                        // Wheel zoom engages on hover so page scroll is not hijacked permanently.
                        scrollWheelZoom: false,
                        wheelPxPerZoomLevel: 90,
                        wheelDebounceTime: 20,
                        zoomSnap: 0.5,
                        zoomDelta: 0.5
                    });

                    const mapEl = this.map.getContainer();
                    mapEl.addEventListener('mouseenter', () => this.map.scrollWheelZoom.enable());
                    mapEl.addEventListener('mouseleave', () => this.map.scrollWheelZoom.disable());
                    // At zoom limits, release the wheel so the page keeps scrolling.
                    mapEl.addEventListener('wheel', (e) => {
                        const z = this.map.getZoom();
                        const goingIn = e.deltaY < 0;
                        if ((goingIn && z >= 19) || (!goingIn && z <= 3)) {
                            this.map.scrollWheelZoom.disable();
                        }
                    }, { passive: true });

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(this.map);

                    this.circle = L.circle([this.lat, this.lng], {
                        color: '#F8A926',
                        fillColor: '#F8A926',
                        fillOpacity: 0.18,
                        weight: 2,
                        radius: this.radius
                    }).addTo(this.map);

                    this.marker = L.marker([this.lat, this.lng], {
                        draggable: true
                    }).addTo(this.map);

                    this.marker.bindPopup(`<b>${this.venue}</b><br>Tarik pin ini untuk laraskan koordinat`).openPopup();

                    this.marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        this.updateCoords(pos.lat, pos.lng);
                    });

                    this.map.on('click', (e) => {
                        this.updateCoords(e.latlng.lat, e.latlng.lng);
                    });
                } else {
                    this.map.invalidateSize();
                }
            },

            updateCoords(newLat, newLng) {
                this.lat = Number(newLat.toFixed(7));
                this.lng = Number(newLng.toFixed(7));
                if (this.marker) {
                    this.marker.setLatLng([this.lat, this.lng]);
                }
                if (this.circle) {
                    this.circle.setLatLng([this.lat, this.lng]);
                }
            },

            updateRadius(val) {
                this.radius = parseInt(val, 10);
                if (this.circle) {
                    this.circle.setRadius(this.radius);
                }
            },

            detectCurrentLocation() {
                if (!navigator.geolocation) {
                    alert('Pelayar anda tidak menyokong fungsi Geolocation.');
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const cLat = pos.coords.latitude;
                        const cLng = pos.coords.longitude;
                        this.updateCoords(cLat, cLng);
                        if (this.map) {
                            this.map.setView([cLat, cLng], 18);
                            if (this.marker) {
                                this.marker.bindPopup('<b>📍 Your Current Location</b><br>Pin moved to current coordinates').openPopup();
                            }
                        }
                    },
                    (err) => {
                        alert('Failed to detect current location: ' + err.message);
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            }
        };
    }
</script>
@endpush
