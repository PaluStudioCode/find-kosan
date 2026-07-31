<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useDraggable } from '@vueuse/core';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';

import {  Navigation, Map, Settings2, X } from 'lucide-vue-next';
import EmptyState from '@/components/EmptyState.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Label } from '@/components/ui/label';

const props = defineProps({
    allKos: Array,
});

const latitude = ref(null);
const longitude = ref(null);
const radius = ref(5);
const mapContainer = ref(null);
const cardRef = ref(null);
const dragHandleRef = ref(null);
const detectingLocation = ref(false);
const isSettingsOpen = ref(false);

const { style: cardStyle } = useDraggable(cardRef, {
    initialValue: { x: 20, y: 100 },
    handle: dragHandleRef
});

let map = null;
let markers = [];
let userMarker = null;
let radiusCircle = null;

// Haversine formula to calculate distance between two coordinates in km
const getDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
};

// Filtered Kos list with calculated distance
const filteredKos = computed(() => {
    if (!latitude.value || !longitude.value) {
        return props.allKos.map(kos => ({ ...kos, distance: null }));
    }

    const filtered = [];
    props.allKos.forEach(kos => {
        if (kos.latitude && kos.longitude) {
            const dist = getDistance(latitude.value, longitude.value, kos.latitude, kos.longitude);
            if (dist <= radius.value) {
                filtered.push({ ...kos, distance: dist });
            }
        }
    });

    // Sort by distance
    return filtered.sort((a, b) => a.distance - b.distance);
});

const initMap = () => {
    // Default center (Indonesia) if no location
    const centerLat = latitude.value || -0.7893;
    const centerLng = longitude.value || 113.9213;
    const zoom = latitude.value ? 13 : 5;

    map = L.map(mapContainer.value).setView([centerLat, centerLng], zoom);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    // Allow user to click anywhere on the map to set location
    map.on('click', (e) => {
        latitude.value = e.latlng.lat;
        longitude.value = e.latlng.lng;
        updateMapMarkers();
    });

    updateMapMarkers();
};

const updateMapMarkers = () => {
    if (!map) return;

    // Clear existing markers
    markers.forEach(m => map.removeLayer(m));
    markers = [];

    // Add user marker and circle if we have location
    if (userMarker) map.removeLayer(userMarker);
    if (radiusCircle) map.removeLayer(radiusCircle);

    if (latitude.value && longitude.value) {
        userMarker = L.marker([latitude.value, longitude.value], {
            draggable: true,
            icon: L.divIcon({
                className: 'custom-user-marker',
                html: `<div style="width: 20px; height: 20px; background-color: #3b82f6; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5); cursor: grab;"></div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        }).addTo(map).bindPopup('Lokasi Anda (Bisa digeser/klik peta)');

        // Update location when the marker is dragged and dropped
        userMarker.on('dragend', (e) => {
            const position = e.target.getLatLng();
            latitude.value = position.lat;
            longitude.value = position.lng;
            updateMapMarkers();
        });

        radiusCircle = L.circle([latitude.value, longitude.value], {
            color: '#3b82f6',
            fillColor: '#3b82f6',
            fillOpacity: 0.1,
            radius: radius.value * 1000 // in meters
        }).addTo(map);
    }

    // Add markers for filtered kos
    const kosList = latitude.value && longitude.value ? filteredKos.value : props.allKos;
    
    kosList.forEach(kos => {
        if (kos.latitude && kos.longitude) {
            const marker = L.marker([kos.latitude, kos.longitude], {
                icon: L.divIcon({
                    className: 'custom-kos-marker',
                    html: `<div style="width: 30px; height: 30px; display:flex; align-items:center; justify-content:center; background-color: #ef4444; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg></div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                })
            }).addTo(map);

            const photoHtml = kos.photos && kos.photos.length > 0 
                ? `<img src="${kos.photos[0].file_path}" class="w-full h-24 sm:h-32 object-cover" />`
                : `<div class="w-full h-24 sm:h-32 bg-slate-100 flex items-center justify-center text-slate-400 text-xs">Tidak ada foto</div>`;
            
            const facilitiesHtml = kos.facilities.slice(0, 2).map(f => `<span class="px-1 sm:px-1.5 py-0.5 bg-slate-100 text-[9px] sm:text-[10px] font-medium rounded text-slate-600">${f.name}</span>`).join('');
            const moreFacilitiesHtml = kos.facilities.length > 2 ? `<span class="px-1 sm:px-1.5 py-0.5 bg-slate-100 text-[9px] sm:text-[10px] font-medium rounded text-slate-600">+${kos.facilities.length - 2}</span>` : '';

            marker.bindPopup(`
                <div class="flex flex-col w-full overflow-hidden rounded-xl">
                    ${photoHtml}
                    <div class="p-3 sm:p-4 bg-white">
                        <h3 class="font-bold text-[13px] sm:text-[15px] mb-0.5 sm:mb-1 leading-tight text-slate-900">${kos.name}</h3>
                        <p class="text-[10px] sm:text-[12px] text-slate-500 mb-2 sm:mb-3 flex items-center gap-1 font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 flex-shrink-0"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <span class="truncate">${kos.city}, ${kos.district}</span>
                        </p>
                        <div class="flex flex-wrap gap-1 sm:gap-1.5 mb-3 sm:mb-4">
                            ${facilitiesHtml}
                            ${moreFacilitiesHtml}
                        </div>
                        <a href="${route('public.kos.show', kos.id)}" class="flex items-center justify-center gap-1 text-[11px] sm:text-[13px] font-bold bg-teal-500 hover:bg-teal-600 !text-white px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg transition-all w-full hover:-translate-y-0.5 shadow-sm">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            `);
            markers.push(marker);
        }
    });
};

// Re-center map to fit bounds of the radius circle if location exists
const fitMapToRadius = () => {
    if (map && radiusCircle) {
        map.fitBounds(radiusCircle.getBounds(), { padding: [20, 20], maxZoom: 15 });
    }
};

const getLocation = (silent = false) => {
    if (navigator.geolocation) {
        detectingLocation.value = true;
        navigator.geolocation.getCurrentPosition(
            (position) => {
                latitude.value = position.coords.latitude;
                longitude.value = position.coords.longitude;
                detectingLocation.value = false;
                updateMapMarkers();
                fitMapToRadius();
            },
            (error) => {
                detectingLocation.value = false;
                if (!silent) {
                    alert('Tidak dapat mengambil lokasi. Pastikan GPS aktif dan Anda memberikan izin lokasi.');
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else if (!silent) {
        alert('Geolocation tidak didukung oleh browser Anda.');
    }
};

// Watch radius to instantly update the map visualization and the markers
watch(radius, () => {
    updateMapMarkers();
    fitMapToRadius();
});

onMounted(() => {
    initMap();
    // Auto-detect location on page load (silent mode: no alert if denied)
    getLocation(true);
});
</script>

<template>
    <PublicLayout>
        <Head title="Cari Kos Terdekat" />

        <div class="w-full h-[calc(100vh-65px)] relative z-0">
            <!-- Settings Toggle Button (Mobile Only) -->
            <button 
                :class="[
                    'md:hidden fixed bottom-6 right-6 z-[400] items-center justify-center w-14 h-14 bg-teal-600 text-white rounded-full shadow-[0_4px_20px_rgb(0,0,0,0.2)] hover:bg-teal-700 active:scale-95 transition-all',
                    isSettingsOpen ? 'hidden' : 'flex'
                ]"
                @click="isSettingsOpen = true"
            >
                <Settings2 class="w-6 h-6" />
            </button>

            <!-- Floating Controls Overlay -->
            <div 
                ref="cardRef"
                :style="cardStyle"
                class="fixed z-[400] w-[320px] bg-white/95 backdrop-blur-xl p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 flex-col gap-5 md:flex"
                :class="isSettingsOpen ? 'flex' : 'hidden'"
            >
                <button 
                    @click="isSettingsOpen = false" 
                    class="md:hidden absolute top-4 right-4 text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-full p-1.5 transition-colors"
                >
                    <X class="w-4 h-4" />
                </button>

                <div ref="dragHandleRef" class="w-full cursor-grab active:cursor-grabbing flex justify-center pb-1 -mt-2 opacity-50 hover:opacity-100 transition-opacity">
                    <div class="w-12 h-1.5 bg-slate-300 rounded-full"></div>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2 mb-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                            <Map class="w-4 h-4" />
                        </span>
                        Cari Kos Sekitar
                    </h2>
                    <p class="text-xs text-slate-500 leading-relaxed font-medium">Gunakan GPS, klik area peta, atau geser pin biru untuk menentukan titik tengah pencarian Anda.</p>
                </div>

                <Button @click="getLocation(false)" size="default" class="w-full gap-2 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-bold shadow-md h-12 transition-all hover:-translate-y-0.5" :disabled="detectingLocation">
                    <svg v-if="detectingLocation" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <Navigation v-else class="w-4 h-4" />
                    {{ detectingLocation ? 'Mendeteksi Lokasi...' : (latitude ? 'Perbarui Lokasi' : 'Deteksi Lokasi Saya') }}
                </Button>
                
                <div class="w-full text-left transition-opacity duration-300" :class="{ 'opacity-50 pointer-events-none': !latitude }">
                    <div class="flex justify-between items-center mb-3">
                        <Label class="text-sm font-bold text-slate-700">Radius Jangkauan</Label>
                        <span class="text-teal-700 font-bold bg-teal-50 px-3 py-1 rounded-full text-xs border border-teal-100">{{ radius }} KM</span>
                    </div>
                    <input 
                        type="range" 
                        v-model.number="radius" 
                        min="1" 
                        max="50" 
                        step="1"
                        class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30" 
                        :disabled="!latitude"
                    />
                    <div class="flex justify-between text-[10px] text-slate-400 mt-2 font-bold tracking-wider">
                        <span>1 KM</span>
                        <span>50 KM</span>
                    </div>
                </div>

                <div v-if="latitude" class="text-xs text-center text-slate-600 bg-slate-50 p-3 rounded-xl font-medium border border-slate-100 shadow-inner">
                    <span class="font-bold text-teal-600 text-sm">{{ filteredKos.length }}</span> properti kos ditemukan
                </div>
            </div>

            <div ref="mapContainer" class="w-full h-full z-0"></div>
        </div>
    </PublicLayout>
</template>

<style>
/* Leaflet popups override for Tailwind */
.leaflet-popup-content-wrapper {
    border-radius: 12px !important;
    padding: 0 !important;
    overflow: hidden;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
}
.leaflet-popup-content {
    margin: 0 !important;
    width: 200px !important;
}
@media (min-width: 640px) {
    .leaflet-popup-content {
        width: 240px !important;
    }
}
.leaflet-popup-close-button {
    color: white !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.8);
    margin-top: 4px !important;
    margin-right: 4px !important;
    z-index: 10;
}
.leaflet-container {
    z-index: 10; /* Lower than sticky header */
}

/* Custom range slider styling */
input[type=range]::-webkit-slider-thumb {
    -webkit-appearance: none;
    height: 24px;
    width: 24px;
    border-radius: 50%;
    background: #14b8a6; /* teal-500 */
    cursor: pointer;
    margin-top: -8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
input[type=range]::-moz-range-thumb {
    height: 24px;
    width: 24px;
    border-radius: 50%;
    background: #14b8a6; /* teal-500 */
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
</style>
