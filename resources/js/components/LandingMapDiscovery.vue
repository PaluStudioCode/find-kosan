<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Navigation, Pause, Play, Search, SlidersHorizontal } from 'lucide-vue-next';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const fallbackItems = [
    { id: 'demo-1', name: 'Kos Area Kampus', city: 'Makassar', district: 'Rappocini', latitude: -5.1572, longitude: 119.4308 },
    { id: 'demo-2', name: 'Kos Panakkukang', city: 'Makassar', district: 'Panakkukang', latitude: -5.1462, longitude: 119.4481 },
    { id: 'demo-3', name: 'Kos Tamalate', city: 'Makassar', district: 'Tamalate', latitude: -5.1714, longitude: 119.4172 },
    { id: 'demo-4', name: 'Kos Pettarani', city: 'Makassar', district: 'Rappocini', latitude: -5.151, longitude: 119.4389 },
    { id: 'demo-5', name: 'Kos Boulevard', city: 'Makassar', district: 'Panakkukang', latitude: -5.1396, longitude: 119.4524 },
];

const displayItems = computed(() => props.items.length ? props.items : fallbackItems);
const mapContainer = ref(null);
const radiusKm = ref(2);
const nearbyCount = ref(0);
const autoAnimation = ref(true);

let map;
let radiusCircle;
let userMarker;
let animationFrame;
let resizeObserver;
let visibilityObserver;
let isVisible = true;
let markerRecords = [];
let minimumRadius = 1;
let maximumRadius = 5;

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const getDistance = (latitude1, longitude1, latitude2, longitude2) => {
    const earthRadius = 6371;
    const latitudeDelta = (latitude2 - latitude1) * Math.PI / 180;
    const longitudeDelta = (longitude2 - longitude1) * Math.PI / 180;
    const calculation =
        Math.sin(latitudeDelta / 2) ** 2
        + Math.cos(latitude1 * Math.PI / 180)
        * Math.cos(latitude2 * Math.PI / 180)
        * Math.sin(longitudeDelta / 2) ** 2;

    return earthRadius * 2 * Math.atan2(Math.sqrt(calculation), Math.sqrt(1 - calculation));
};

const createKosIcon = (index) => L.divIcon({
    className: 'landing-kos-icon',
    html: `
        <div class="landing-kos-marker" style="--marker-delay: ${index * 180}ms">
            <span></span>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 10.5 12 3l9 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.5v-9Z"></path>
                <path d="M9 21v-7h6v7"></path>
            </svg>
        </div>
    `,
    iconSize: [38, 38],
    iconAnchor: [19, 19],
});

const updateRadiusState = () => {
    if (!radiusCircle) return;

    radiusCircle.setRadius(radiusKm.value * 1000);
    let count = 0;

    markerRecords.forEach((record) => {
        const insideRadius = record.distance <= radiusKm.value;
        record.element?.classList.toggle('is-outside', !insideRadius);
        record.element?.classList.toggle('is-inside', insideRadius);
        if (insideRadius) count += 1;
    });

    nearbyCount.value = count;
};

const handleManualRadius = (event) => {
    autoAnimation.value = false;
    radiusKm.value = Number(event.target.value);
    updateRadiusState();
};

const toggleAnimation = () => {
    autoAnimation.value = !autoAnimation.value;
};

const initializeMap = () => {
    const coordinates = displayItems.value.map((item) => [
        Number(item.latitude),
        Number(item.longitude),
    ]);
    const center = coordinates.length > 1
        ? [
            coordinates.reduce((total, coordinate) => total + coordinate[0], 0) / coordinates.length,
            coordinates.reduce((total, coordinate) => total + coordinate[1], 0) / coordinates.length,
        ]
        : coordinates.length === 1
            ? [coordinates[0][0] - 0.008, coordinates[0][1] - 0.006]
            : [-5.1477, 119.4327];

    map = L.map(mapContainer.value, {
        zoomControl: false,
        attributionControl: true,
        scrollWheelZoom: false,
        dragging: true,
        doubleClickZoom: false,
    }).setView(center, 13);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        subdomains: 'abcd',
        maxZoom: 19,
    }).addTo(map);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    userMarker = L.marker(center, {
        interactive: false,
        zIndexOffset: 1000,
        icon: L.divIcon({
            className: 'landing-user-icon',
            html: '<div class="landing-user-marker"><span></span></div>',
            iconSize: [24, 24],
            iconAnchor: [12, 12],
        }),
    }).addTo(map);

    radiusCircle = L.circle(center, {
        radius: radiusKm.value * 1000,
        color: '#0f766e',
        weight: 2,
        opacity: 0.8,
        dashArray: '7 7',
        fillColor: '#2dd4bf',
        fillOpacity: 0.13,
        interactive: false,
    }).addTo(map);

    markerRecords = displayItems.value.map((item, index) => {
        const latitude = Number(item.latitude);
        const longitude = Number(item.longitude);
        const marker = L.marker([latitude, longitude], {
            icon: createKosIcon(index),
        }).addTo(map);

        marker.bindPopup(`
            <div class="landing-map-popup">
                <strong>${escapeHtml(item.name)}</strong>
                <span>${escapeHtml([item.district, item.city].filter(Boolean).join(', '))}</span>
            </div>
        `);

        return {
            marker,
            distance: getDistance(center[0], center[1], latitude, longitude),
            element: marker.getElement()?.querySelector('.landing-kos-marker'),
        };
    });

    const furthestDistance = Math.max(...markerRecords.map((record) => record.distance), 2);
    maximumRadius = Math.max(3, Math.ceil(furthestDistance + 0.8));
    minimumRadius = Math.max(0.8, Math.min(2, maximumRadius * 0.35));
    radiusKm.value = minimumRadius;

    const bounds = L.latLngBounds(coordinates);
    if (coordinates.length > 1) {
        map.fitBounds(bounds.pad(0.35), { maxZoom: 14 });
    }

    updateRadiusState();
};

onMounted(() => {
    initializeMap();

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        autoAnimation.value = false;
    }

    const startedAt = performance.now();
    const animate = (timestamp) => {
        animationFrame = window.requestAnimationFrame(animate);
        if (!isVisible || !autoAnimation.value) return;

        const cycle = (Math.sin((timestamp - startedAt) / 1500) + 1) / 2;
        radiusKm.value = Number((minimumRadius + cycle * (maximumRadius - minimumRadius)).toFixed(1));
        updateRadiusState();
    };

    resizeObserver = new ResizeObserver(() => map?.invalidateSize());
    resizeObserver.observe(mapContainer.value);

    visibilityObserver = new IntersectionObserver(([entry]) => {
        isVisible = entry.isIntersecting;
    });
    visibilityObserver.observe(mapContainer.value);

    animate(startedAt);
});

onBeforeUnmount(() => {
    window.cancelAnimationFrame(animationFrame);
    resizeObserver?.disconnect();
    visibilityObserver?.disconnect();
    map?.remove();
});
</script>

<template>
    <div class="landing-map-shell">
        <div ref="mapContainer" class="h-[420px] w-full sm:h-[500px]" />

        <div class="pointer-events-none absolute inset-x-4 top-4 z-[500] sm:inset-x-6 sm:top-6">
            <div class="flex items-center gap-3 rounded-2xl border border-white/80 bg-white/90 px-4 py-3 shadow-lg backdrop-blur-xl">
                <Search class="h-4 w-4 shrink-0 text-teal-700" />
                <div class="min-w-0">
                    <p class="truncate text-xs font-bold text-slate-800">Cari kos di sekitar lokasi</p>
                    <p class="truncate text-[10px] text-slate-400">Geser radius untuk memperluas area</p>
                </div>
            </div>
        </div>

        <div class="absolute bottom-5 left-4 right-4 z-[500] sm:bottom-6 sm:left-6 sm:right-auto sm:w-[290px]">
            <div class="rounded-2xl border border-white/80 bg-white/95 p-4 shadow-xl backdrop-blur-xl">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <SlidersHorizontal class="h-4 w-4 text-teal-700" />
                        <p class="text-xs font-bold text-slate-800">Radius pencarian</p>
                    </div>
                    <button
                        type="button"
                        class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-teal-50 hover:text-teal-700"
                        :aria-label="autoAnimation ? 'Jeda animasi radius' : 'Putar animasi radius'"
                        @click="toggleAnimation"
                    >
                        <Pause v-if="autoAnimation" class="h-3.5 w-3.5" />
                        <Play v-else class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div class="mt-3 flex items-center gap-3">
                    <input
                        :value="radiusKm"
                        type="range"
                        :min="minimumRadius"
                        :max="maximumRadius"
                        step="0.1"
                        class="h-1.5 flex-1 cursor-pointer appearance-none rounded-full bg-slate-200 accent-teal-700"
                        aria-label="Radius pencarian kos"
                        @input="handleManualRadius"
                    />
                    <span class="min-w-14 rounded-lg bg-teal-50 px-2 py-1 text-center text-xs font-bold text-teal-800">
                        {{ radiusKm }} km
                    </span>
                </div>
            </div>
        </div>

        <div class="absolute bottom-32 right-4 z-[500] sm:bottom-6 sm:right-16">
            <div class="flex items-center gap-2 rounded-full border border-white/80 bg-[#0c292b] px-3.5 py-2 text-xs font-bold text-white shadow-xl">
                <Navigation class="h-3.5 w-3.5 text-teal-300" />
                {{ nearbyCount }} kos ditemukan
            </div>
        </div>
    </div>
</template>

<style>
.landing-map-shell {
    position: relative;
    overflow: hidden;
    border: 1px solid #dbe6e2;
    border-radius: 1.75rem;
    background: #e8efec;
    box-shadow: 0 30px 70px -40px rgba(12, 41, 43, 0.55);
}

.landing-map-shell .leaflet-control-attribution {
    font-size: 8px;
}

.landing-map-shell .leaflet-control-zoom {
    overflow: hidden;
    border: 0;
    border-radius: 0.75rem;
    box-shadow: 0 8px 25px rgba(15, 23, 42, 0.14);
}

.landing-user-marker {
    position: relative;
    width: 22px;
    height: 22px;
    border: 4px solid white;
    border-radius: 999px;
    background: #2563eb;
    box-shadow: 0 3px 12px rgba(37, 99, 235, 0.45);
}

.landing-user-marker span {
    position: absolute;
    inset: -9px;
    border: 2px solid rgba(37, 99, 235, 0.25);
    border-radius: inherit;
    animation: landing-user-pulse 1.8s ease-out infinite;
}

.landing-kos-marker {
    position: relative;
    display: flex;
    width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    border: 3px solid white;
    border-radius: 999px;
    background: #f97316;
    color: white;
    box-shadow: 0 5px 16px rgba(194, 65, 12, 0.35);
    transition: opacity 280ms ease, filter 280ms ease, transform 280ms ease;
}

.landing-kos-marker svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: currentColor;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-width: 1.8;
}

.landing-kos-marker span {
    position: absolute;
    inset: -7px;
    border: 2px solid rgba(249, 115, 22, 0.3);
    border-radius: inherit;
    animation: landing-marker-pulse 2s ease-out var(--marker-delay) infinite;
}

.landing-kos-marker.is-outside {
    opacity: 0.38;
    filter: grayscale(0.75);
    transform: scale(0.82);
}

.landing-kos-marker.is-outside span {
    animation-play-state: paused;
    opacity: 0;
}

.landing-map-popup {
    display: flex;
    min-width: 150px;
    flex-direction: column;
    gap: 3px;
    padding: 3px;
}

.landing-map-popup strong {
    color: #0f172a;
    font-size: 12px;
}

.landing-map-popup span {
    color: #64748b;
    font-size: 10px;
}

@keyframes landing-user-pulse {
    from { opacity: 0.8; transform: scale(0.6); }
    to { opacity: 0; transform: scale(1.45); }
}

@keyframes landing-marker-pulse {
    from { opacity: 0.75; transform: scale(0.65); }
    to { opacity: 0; transform: scale(1.35); }
}

@media (prefers-reduced-motion: reduce) {
    .landing-user-marker span,
    .landing-kos-marker span {
        animation: none;
    }
}
</style>
