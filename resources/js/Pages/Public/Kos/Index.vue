<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useDraggable } from '@vueuse/core';
import { Head, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';

import {  Navigation, Map, Settings2, X, Search, Filter, List, MapPin, Lock, Unlock } from 'lucide-vue-next';

const isListView = ref(false); // Default to false (hidden) on all screens initially
const toggleListView = () => {
    isListView.value = !isListView.value;
    if (!isListView.value) {
        // Redraw map on next tick to fix size issues when map container unhides on mobile
        setTimeout(() => {
            if (map) map.invalidateSize();
        }, 100);
    }
};

const handleMarkerHover = (kosId, isHovering) => {
    if (!map || !markerClusterGroup) return;
    
    // Find the marker corresponding to this kosId
    const targetMarker = markers.find(m => m.kosId === kosId);
    if (!targetMarker) return;

    if (isHovering) {
        // Find HTML element
        const iconElement = targetMarker.getElement();
        if (iconElement) {
            const innerDiv = iconElement.querySelector('div');
            if (innerDiv) {
                innerDiv.classList.add('!bg-teal-50', '!border-teal-400');
                const span = innerDiv.querySelector('span');
                if (span) span.classList.add('!text-teal-700');
            }
        }
    } else {
        // Remove classes
        const iconElement = targetMarker.getElement();
        if (iconElement) {
            const innerDiv = iconElement.querySelector('div');
            if (innerDiv) {
                innerDiv.classList.remove('!bg-teal-50', '!border-teal-400');
                const span = innerDiv.querySelector('span');
                if (span) span.classList.remove('!text-teal-700');
            }
        }
    }
};
import EmptyState from '@/components/EmptyState.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import { Label } from '@/components/ui/label';

const props = defineProps({
    allKos: Array,
    filters: Object,
    filterFacilities: Array,
});

const latitude = ref(props.filters?.lat ? parseFloat(props.filters.lat) : null);
const longitude = ref(props.filters?.lng ? parseFloat(props.filters.lng) : null);
const radius = ref(props.filters?.radius ? parseFloat(props.filters.radius) : 5);
const highlightKosId = ref(props.filters?.highlight_kos_id ? parseInt(props.filters.highlight_kos_id) : null);
const minPrice = ref(props.filters?.min_price ? parseFloat(props.filters.min_price) : null);
const maxPrice = ref(props.filters?.max_price ? parseFloat(props.filters.max_price) : null);
const selectedFacilities = ref(props.filters?.facilities ? (Array.isArray(props.filters.facilities) ? props.filters.facilities.map(id => parseInt(id)) : [parseInt(props.filters.facilities)]) : []);

const minPriceDisplay = computed({
    get: () => minPrice.value ? minPrice.value.toLocaleString('id-ID') : '',
    set: (val) => {
        const parsed = parseInt(val.replace(/\D/g, ''), 10);
        minPrice.value = isNaN(parsed) ? null : parsed;
    }
});

const maxPriceDisplay = computed({
    get: () => maxPrice.value ? maxPrice.value.toLocaleString('id-ID') : '',
    set: (val) => {
        const parsed = parseInt(val.replace(/\D/g, ''), 10);
        maxPrice.value = isNaN(parsed) ? null : parsed;
    }
});

const isLocationLocked = ref(false);

const mapContainer = ref(null);
const cardRef = ref(null);
const dragHandleRef = ref(null);
const detectingLocation = ref(false);
const isSettingsOpen = ref(false);

const { style: cardStyle } = useDraggable(cardRef, {
    initialValue: { 
        x: window.innerWidth >= 1024 ? window.innerWidth - 380 : 20, 
        y: 100 
    },
    handle: dragHandleRef
});

let map = null;
let markerClusterGroup = null;
let markers = [];
let userMarker = null;
let radiusCircle = null;

// Format number to Rupiah short string (e.g. 500000 -> 500rb)
const formatPrice = (price) => {
    if (!price) return '-';
    const num = parseFloat(price);
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'jt';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(0) + 'rb';
    }
    return price.toString();
};

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

// We filter the server's response locally as well. 
// This ensures that when the user moves the pin, old markers that fall outside the new radius disappear instantly (Zero lag) while waiting for the server's new data.
const filteredKos = computed(() => {
    if (!latitude.value || !longitude.value) {
        return props.allKos;
    }

    const filtered = [];
    props.allKos.forEach(kos => {
        if (kos.latitude && kos.longitude) {
            const dist = getDistance(latitude.value, longitude.value, kos.latitude, kos.longitude);
            if (dist <= radius.value || radius.value === 51) {
                // Apply local filters for price and facilities to ensure immediate UI feedback
                let matchPrice = true;
                if (minPrice.value && kos.rooms_min_price < minPrice.value) matchPrice = false;
                if (maxPrice.value && kos.rooms_min_price > maxPrice.value) matchPrice = false;

                let matchFacilities = true;
                if (selectedFacilities.value && selectedFacilities.value.length > 0) {
                    const kosFacilityIds = kos.facilities.map(f => f.id);
                    matchFacilities = selectedFacilities.value.every(id => kosFacilityIds.includes(id));
                }

                if (matchPrice && matchFacilities) {
                    filtered.push({ ...kos, distance: dist });
                }
            }
        }
    });

    return filtered.sort((a, b) => a.distance - b.distance);
});

const fetchKosFromServer = () => {
    updateMapMarkers();
    fitMapToRadius();
    
    if (latitude.value && longitude.value) {
        router.get(route('public.kos'), {
            lat: latitude.value,
            lng: longitude.value,
            radius: radius.value,
            min_price: minPrice.value,
            max_price: maxPrice.value,
            facilities: selectedFacilities.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['allKos', 'filters']
        });
    }
};

const searchQuery = ref('');
const isSearchingLocation = ref(false);

const searchLocation = async () => {
    if (!searchQuery.value.trim()) return;
    
    isSearchingLocation.value = true;
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value + ', Indonesia')}&limit=1`);
        const data = await response.json();
        
        if (data && data.length > 0) {
            latitude.value = parseFloat(data[0].lat);
            longitude.value = parseFloat(data[0].lon);
            
            if (map) {
                map.flyTo([latitude.value, longitude.value], 14, {
                    animate: true,
                    duration: 1.5
                });
            }
            
            fetchKosFromServer();
        } else {
            alert('Lokasi tidak ditemukan. Coba gunakan nama kota atau daerah yang lebih spesifik.');
        }
    } catch (error) {
        console.error('Error searching location:', error);
        alert('Terjadi kesalahan saat mencari lokasi.');
    } finally {
        isSearchingLocation.value = false;
    }
};

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

    // Initialize marker cluster group
    markerClusterGroup = L.markerClusterGroup({
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        maxClusterRadius: 40,
        iconCreateFunction: function(cluster) {
            return L.divIcon({ 
                html: `<div class="w-10 h-10 flex items-center justify-center bg-teal-600 text-white font-bold rounded-full border-4 border-white shadow-md text-sm">${cluster.getChildCount()}</div>`, 
                className: 'custom-cluster-icon', 
                iconSize: L.point(40, 40) 
            });
        }
    });
    map.addLayer(markerClusterGroup);

    // Allow user to click anywhere on the map to set location
    map.on('click', (e) => {
        if (isLocationLocked.value) return;
        latitude.value = e.latlng.lat;
        longitude.value = e.latlng.lng;
        fetchKosFromServer();
    });

    updateMapMarkers();
};

const updateMapMarkers = () => {
    if (!map) return;

    // Clear existing markers
    if (markerClusterGroup) {
        markerClusterGroup.clearLayers();
    }
    markers = [];

    // Add user marker and circle if we have location
    if (userMarker) map.removeLayer(userMarker);
    if (radiusCircle) map.removeLayer(radiusCircle);

    if (latitude.value && longitude.value) {
        userMarker = L.marker([latitude.value, longitude.value], {
            draggable: !isLocationLocked.value,
            icon: L.divIcon({
                className: 'custom-user-marker',
                html: `<div style="width: 20px; height: 20px; background-color: ${isLocationLocked.value ? '#94a3b8' : '#3b82f6'}; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5); cursor: ${isLocationLocked.value ? 'not-allowed' : 'grab'}; display: flex; align-items: center; justify-content: center;">
                        ${isLocationLocked.value ? '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>' : ''}
                       </div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        }).addTo(map).bindPopup(isLocationLocked.value ? 'Lokasi Anda (Terkunci)' : 'Lokasi Anda (Bisa digeser/klik peta)');

        // Update location when the marker is dragged and dropped
        userMarker.on('dragend', (e) => {
            if (isLocationLocked.value) return;
            const position = e.target.getLatLng();
            latitude.value = position.lat;
            longitude.value = position.lng;
            fetchKosFromServer();
        });

        if (radius.value < 51) {
            radiusCircle = L.circle([latitude.value, longitude.value], {
                color: '#3b82f6',
                fillColor: '#3b82f6',
                fillOpacity: 0.1,
                radius: radius.value * 1000 // in meters
            }).addTo(map);
        }
    }

    // Add markers for filtered kos
    const kosList = filteredKos.value;
    
    kosList.forEach(kos => {
        if (kos.latitude && kos.longitude) {
            const formattedPrice = formatPrice(kos.rooms_min_price);
            
            const marker = L.marker([kos.latitude, kos.longitude], {
                icon: L.divIcon({
                    className: 'custom-price-marker',
                    html: `
                        <div class="px-2 py-1 bg-white border border-slate-200 shadow-md rounded-full flex items-center justify-center whitespace-nowrap group hover:bg-teal-50 hover:border-teal-400 transition-all cursor-pointer relative z-10 before:absolute before:bottom-[-6px] before:left-1/2 before:-translate-x-1/2 before:w-3 before:h-3 before:bg-white before:border-r before:border-b before:border-slate-200 before:rotate-45 group-hover:before:bg-teal-50 group-hover:before:border-teal-400">
                            <span class="text-xs font-bold text-slate-800 group-hover:text-teal-700 relative z-20">Rp ${formattedPrice}</span>
                        </div>
                    `,
                    iconSize: [60, 30],
                    iconAnchor: [30, 35],
                    popupAnchor: [0, -35]
                })
            });

            // Store kos id on marker for interaction later
            marker.kosId = kos.id;

            const photoHtml = kos.photos && kos.photos.length > 0 
                ? `<img src="${kos.photos[0].file_path}" class="w-full h-24 sm:h-32 object-cover" />`
                : `<div class="w-full h-24 sm:h-32 bg-slate-100 flex items-center justify-center text-slate-400 text-xs">Tidak ada foto</div>`;
            
            const facilitiesHtml = kos.facilities.slice(0, 2).map(f => `<span class="px-1 sm:px-1.5 py-0.5 bg-slate-100 text-[9px] sm:text-[10px] font-medium rounded text-slate-600">${f.name}</span>`).join('');
            const moreFacilitiesHtml = kos.facilities.length > 2 ? `<span class="px-1 sm:px-1.5 py-0.5 bg-slate-100 text-[9px] sm:text-[10px] font-medium rounded text-slate-600">+${kos.facilities.length - 2}</span>` : '';

            marker.bindPopup(`
                <div class="flex flex-col w-full overflow-hidden rounded-xl">
                    ${photoHtml}
                    <div class="p-3 sm:p-4 bg-white">
                        <div class="flex justify-between items-start gap-2 mb-1">
                            <h3 class="font-bold text-[13px] sm:text-[15px] leading-tight text-slate-900">${kos.name}</h3>
                            <span class="text-xs font-bold text-teal-600 shrink-0">Rp ${formattedPrice}</span>
                        </div>
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
            markerClusterGroup.addLayer(marker);

            if (highlightKosId.value && kos.id === highlightKosId.value) {
                // Use setTimeout to ensure map is fully loaded before opening popup
                setTimeout(() => {
                    // Zoom to cluster if inside one
                    const parent = markerClusterGroup.getVisibleParent(marker);
                    if (parent && parent !== marker) {
                        markerClusterGroup.zoomToShowLayer(marker, () => {
                            marker.openPopup();
                        });
                    } else {
                        marker.openPopup();
                    }
                }, 300);
            }
        }
    });
};

// Re-center map to fit bounds of the radius circle if location exists
const fitMapToRadius = () => {
    if (map) {
        if (radius.value < 51 && radiusCircle) {
            map.fitBounds(radiusCircle.getBounds(), { padding: [20, 20], maxZoom: 15 });
        } else if (radius.value === 51 && markers.length > 0) {
            map.fitBounds(markerClusterGroup.getBounds(), { padding: [50, 50], maxZoom: 13 });
        }
    }
};

const getLocation = (silent = false) => {
    if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        if (!silent) alert('Fitur lokasi membutuhkan koneksi HTTPS atau localhost.');
        detectingLocation.value = false;
        return;
    }

    if (navigator.geolocation) {
        detectingLocation.value = true;
        
        // Manual fallback timeout (jaga-jaga jika browser diam saja/silent fail)
        const fallback = setTimeout(() => {
            if (detectingLocation.value) {
                detectingLocation.value = false;
                if (!silent) alert('Waktu deteksi lokasi habis. Browser mungkin memblokir akses GPS.');
            }
        }, 8000);

        navigator.geolocation.getCurrentPosition(
            (position) => {
                clearTimeout(fallback);
                latitude.value = position.coords.latitude;
                longitude.value = position.coords.longitude;
                detectingLocation.value = false;
                fetchKosFromServer();
            },
            (error) => {
                clearTimeout(fallback);
                detectingLocation.value = false;
                if (!silent) {
                    alert('Tidak dapat mengambil lokasi. Pastikan GPS aktif dan Anda memberikan izin lokasi.');
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 7000,
                maximumAge: 0
            }
        );
    } else if (!silent) {
        alert('Geolocation tidak didukung oleh browser Anda.');
    }
};

// Watch radius specifically to resize local circle and fetch from server (Realtime Radius)
let radiusTimeout;
watch(radius, () => {
    if (radiusCircle && map) {
        if (radius.value < 51) {
            radiusCircle.setRadius(radius.value * 1000);
            if (!map.hasLayer(radiusCircle)) {
                radiusCircle.addTo(map);
            }
        } else {
            map.removeLayer(radiusCircle);
        }
    }
    
    // Auto fetch when radius changes
    clearTimeout(radiusTimeout);
    radiusTimeout = setTimeout(() => {
        fetchKosFromServer();
    }, 500);
});

// Watch props to redraw map when new data arrives from server
watch(() => props.allKos, () => {
    updateMapMarkers();
    fitMapToRadius();
}, { deep: true });

onMounted(() => {
    initMap();
    
    // Auto-open settings card on desktop/laptop (>= 1024px)
    if (window.innerWidth >= 1024) {
        isSettingsOpen.value = true;
    }

    // Auto-detect location on page load (silent mode: no alert if denied)
    getLocation(true);
});
</script>

<template>
    <PublicLayout>
        <Head title="Cari Kos Terdekat" />

        <div class="w-full h-[calc(100vh-65px)] relative flex flex-col lg:flex-row overflow-hidden z-0">
            
            <!-- Left Panel / Bottom Sheet (List View) -->
            <div 
                class="w-full lg:w-[45%] xl:w-[40%] shrink-0 bg-slate-50 border-r border-slate-200 flex flex-col z-20 absolute lg:relative inset-0 transition-all duration-300 ease-in-out"
                :class="isListView ? 'translate-y-0 lg:translate-x-0' : 'translate-y-full lg:-translate-x-full lg:!w-0 lg:opacity-0'"
            >
                <!-- List Header (Sticky) -->
                <div class="p-5 bg-white border-b border-slate-200 shadow-sm z-10 sticky top-0">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 leading-tight">Kos di Sekitarmu</h1>
                            <p class="text-xs text-slate-500 font-medium mt-1">Ditemukan <span class="font-bold text-teal-600">{{ filteredKos.length }}</span> kos dalam jangkauan</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button 
                                @click="isSettingsOpen = true"
                                class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition-colors flex items-center gap-2 lg:hidden"
                            >
                                <Filter class="w-4 h-4" />
                                <span class="text-xs font-bold">Filter</span>
                            </button>
                            <button 
                                @click="toggleListView"
                                class="hidden lg:flex p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg transition-colors items-center"
                                title="Tutup Daftar"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Geocoding Search (Desktop only in this panel) -->
                    <div class="relative w-full hidden lg:block">
                        <input 
                            v-model="searchQuery" 
                            @keyup.enter="searchLocation"
                            type="text" 
                            placeholder="Cari lokasi, kota, kampus..." 
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all font-medium text-slate-700 placeholder:text-slate-400"
                            :disabled="isSearchingLocation"
                        />
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <Search class="w-4 h-4 text-slate-400" />
                        </div>
                        <button 
                            v-if="searchQuery" 
                            @click="searchLocation"
                            class="absolute inset-y-1.5 right-1.5 px-3 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-lg transition-colors flex items-center disabled:opacity-50"
                            :disabled="isSearchingLocation"
                        >
                            <svg v-if="isSearchingLocation" class="w-3 h-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span v-else>Cari</span>
                        </button>
                    </div>
                </div>

                <!-- List Content -->
                <div class="flex-1 overflow-y-auto p-4 lg:p-5">
                    <div v-if="filteredKos.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 mb-4">
                            <MapPin class="w-8 h-8" />
                        </div>
                        <h3 class="text-base font-bold text-slate-700 mb-1">Kos Tidak Ditemukan</h3>
                        <p class="text-xs text-slate-500 max-w-[250px]">Coba perluas jangkauan radius atau kurangi filter untuk melihat lebih banyak kos.</p>
                        <Button @click="radius = 51" variant="outline" class="mt-4 rounded-xl text-xs font-bold border-slate-200">Reset Radius</Button>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-4 pb-20 lg:pb-0">
                        <div 
                            v-for="kos in filteredKos" 
                            :key="kos.id"
                            @mouseenter="handleMarkerHover(kos.id, true)"
                            @mouseleave="handleMarkerHover(kos.id, false)"
                            class="group bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-teal-200 transition-all duration-300 cursor-pointer flex flex-col"
                            @click="router.visit(route('public.kos.show', kos.id))"
                        >
                            <!-- Image -->
                            <div class="relative h-40 w-full bg-slate-100 overflow-hidden">
                                <img v-if="kos.photos && kos.photos.length > 0" :src="kos.photos[0].file_path" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" :alt="kos.name" />
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                                    <MapPin class="w-6 h-6 opacity-50" />
                                </div>
                                <div class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-md text-[10px] font-bold text-slate-700 flex items-center gap-1 shadow-sm">
                                    <Navigation class="w-3 h-3 text-teal-600" />
                                    {{ kos.distance ? kos.distance.toFixed(1) + ' km' : '0 km' }}
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="p-4 flex flex-col flex-1">
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <h3 class="font-bold text-[14px] text-slate-900 leading-tight group-hover:text-teal-700 transition-colors line-clamp-1">{{ kos.name }}</h3>
                                </div>
                                
                                <p class="text-[11px] text-slate-500 mb-3 flex items-center gap-1 font-medium line-clamp-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400 flex-shrink-0"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    {{ kos.district }}, {{ kos.city }}
                                </p>

                                <div class="flex flex-wrap gap-1 mb-auto">
                                    <span v-for="facility in kos.facilities.slice(0, 3)" :key="facility.id" class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 text-[9px] font-bold text-slate-600 rounded">
                                        {{ facility.name }}
                                    </span>
                                    <span v-if="kos.facilities.length > 3" class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 text-[9px] font-bold text-slate-600 rounded">
                                        +{{ kos.facilities.length - 3 }}
                                    </span>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-xs font-medium text-slate-500">Mulai dari</span>
                                    <span class="text-sm font-black text-teal-600">Rp {{ formatPrice(kos.rooms_min_price) }}<span class="text-[10px] text-slate-400 font-medium">/bln</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel (Map View) -->
            <div class="flex-1 w-full h-full relative z-0">
                <!-- Mobile View Toggles -->
                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-[400] flex lg:hidden shadow-[0_8px_30px_rgb(0,0,0,0.12)] rounded-full p-1 bg-white border border-slate-100">
                    <button 
                        @click="isListView = false"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-bold transition-all"
                        :class="!isListView ? 'bg-teal-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                    >
                        <Map class="w-4 h-4" /> Peta
                    </button>
                    <button 
                        @click="isListView = true"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-bold transition-all"
                        :class="isListView ? 'bg-teal-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                    >
                        <List class="w-4 h-4" /> Daftar
                    </button>
                </div>

                <!-- Desktop Open List View Button (Shown when list is collapsed) -->
                <button 
                    v-if="!isListView"
                    class="hidden lg:flex absolute top-6 left-6 z-[400] items-center gap-2 px-4 py-2.5 bg-white text-slate-700 font-bold text-sm rounded-xl shadow-[0_4px_20px_rgb(0,0,0,0.1)] hover:bg-slate-50 border border-slate-200 transition-all"
                    @click="toggleListView"
                >
                    <List class="w-4 h-4" /> Buka Daftar
                </button>

                <!-- Settings Toggle Button (Desktop & Mobile Map View) -->
                <button 
                    v-show="!isListView || window?.innerWidth >= 1024"
                    :class="[
                        'absolute top-6 right-6 z-[400] items-center justify-center w-12 h-12 bg-white text-slate-700 rounded-xl shadow-[0_4px_20px_rgb(0,0,0,0.1)] hover:bg-slate-50 border border-slate-200 active:scale-95 transition-all',
                        isSettingsOpen ? 'hidden' : 'flex'
                    ]"
                    @click="isSettingsOpen = true"
                >
                    <Filter class="w-5 h-5" />
                    <!-- Notification dot if filters active -->
                    <span v-if="radius < 51 || minPrice || maxPrice || selectedFacilities.length" class="absolute top-3 right-3 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                </button>

            <!-- Floating Controls Overlay -->
            <div 
                ref="cardRef"
                :style="cardStyle"
                class="fixed z-[400] w-[340px] bg-white/95 backdrop-blur-xl p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 flex-col gap-5 max-h-[85vh] overflow-y-auto"
                :class="isSettingsOpen ? 'flex' : 'hidden'"
            >
                <button 
                    @click="isSettingsOpen = false" 
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-full p-1.5 transition-colors z-10"
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
                    <p class="text-xs text-slate-500 leading-relaxed font-medium">Gunakan GPS, klik area peta, atau ketik nama lokasi yang ingin dituju.</p>
                </div>

                <!-- Geocoding Search Bar -->
                <div class="relative w-full lg:hidden">
                    <input 
                        v-model="searchQuery" 
                        @keyup.enter="searchLocation"
                        type="text" 
                        placeholder="Cari lokasi, kota, kampus..." 
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all font-medium text-slate-700 placeholder:text-slate-400"
                        :disabled="isSearchingLocation"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <Search class="w-4 h-4 text-slate-400" />
                    </div>
                    <button 
                        v-if="searchQuery" 
                        @click="searchLocation"
                        class="absolute inset-y-1.5 right-1.5 px-3 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-lg transition-colors flex items-center disabled:opacity-50"
                        :disabled="isSearchingLocation"
                    >
                        <svg v-if="isSearchingLocation" class="w-3 h-3 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span v-else>Cari</span>
                    </button>
                </div>

                <div class="flex items-center gap-3 lg:hidden">
                    <div class="h-px bg-slate-100 flex-1"></div>
                    <span class="text-[10px] font-bold text-slate-300 uppercase tracking-wider">ATAU</span>
                    <div class="h-px bg-slate-100 flex-1"></div>
                </div>

                <Button @click="getLocation(false)" size="default" class="w-full gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold shadow-md h-11 transition-all hover:-translate-y-0.5" :disabled="detectingLocation">
                    <svg v-if="detectingLocation" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <Navigation v-else class="w-4 h-4" />
                    {{ detectingLocation ? 'Mendeteksi...' : 'Gunakan GPS Saya' }}
                </Button>
                
                <div class="w-full text-left transition-opacity duration-300" :class="{ 'opacity-50 pointer-events-none': !latitude }">
                    <div class="space-y-5 mt-4">
                        <!-- Radius Slider -->
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <Label class="text-sm font-bold text-slate-700">Radius Jangkauan</Label>
                                <div class="flex items-center gap-3">
                                    <button 
                                        @click="isLocationLocked = !isLocationLocked; updateMapMarkers()"
                                        type="button"
                                        class="p-1.5 rounded-lg transition-colors border shadow-sm flex items-center justify-center"
                                        :class="isLocationLocked ? 'bg-slate-800 border-slate-800 text-white hover:bg-slate-700' : 'bg-white border-slate-200 text-slate-400 hover:text-slate-600 hover:bg-slate-50'"
                                        :title="isLocationLocked ? 'Buka Kunci Titik Lokasi' : 'Kunci Titik Lokasi'"
                                    >
                                        <Lock v-if="isLocationLocked" class="w-3.5 h-3.5" />
                                        <Unlock v-else class="w-3.5 h-3.5" />
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <template v-if="radius < 51">
                                            <input 
                                                type="number" 
                                                v-model.number="radius" 
                                                min="0.5" 
                                                max="50" 
                                                step="0.5"
                                                class="w-16 px-2 py-1 text-xs text-center border border-teal-200 rounded-md focus:ring-teal-500 focus:border-teal-500 text-teal-700 font-bold bg-teal-50 shadow-sm"
                                            />
                                            <span class="text-xs font-bold text-slate-500">KM</span>
                                        </template>
                                        <span v-else class="text-teal-700 font-bold bg-teal-50 px-3 py-1 rounded-full text-xs border border-teal-100">
                                            Tak Terbatas
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <input 
                                type="range" 
                                v-model.number="radius" 
                                min="0.5" 
                                max="51" 
                                step="0.5"
                                class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30" 
                                :disabled="!latitude || detectingLocation"
                            />
                            <div class="flex justify-between text-[10px] text-slate-400 mt-2 font-bold tracking-wider">
                                <span>500 M</span>
                                <span>∞</span>
                            </div>
                        </div>

                        <!-- Price Range Filter -->
                        <div class="pt-3 border-t border-slate-100">
                            <Label class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
                                <Filter class="w-3.5 h-3.5 text-slate-400" />
                                Rentang Harga / Bulan
                            </Label>
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                        <span class="text-xs font-bold text-slate-400">Rp</span>
                                    </div>
                                    <input 
                                        type="text" 
                                        v-model="minPriceDisplay" 
                                        placeholder="Min"
                                        class="w-full pl-8 pr-2 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-slate-700 font-medium placeholder:text-slate-300"
                                    />
                                </div>
                                <span class="text-slate-300 font-bold">-</span>
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                        <span class="text-xs font-bold text-slate-400">Rp</span>
                                    </div>
                                    <input 
                                        type="text" 
                                        v-model="maxPriceDisplay" 
                                        placeholder="Max"
                                        class="w-full pl-8 pr-2 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-slate-700 font-medium placeholder:text-slate-300"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Essential Facilities Filter -->
                        <div class="pt-3 border-t border-slate-100" v-if="filterFacilities && filterFacilities.length > 0">
                            <Label class="text-sm font-bold text-slate-700 mb-3 block">Fasilitas Pilihan</Label>
                            <div class="grid grid-cols-2 gap-3">
                                <label v-for="facility in filterFacilities" :key="facility.id" class="flex items-start gap-2 cursor-pointer group">
                                    <div class="relative flex items-center mt-0.5">
                                        <input 
                                            type="checkbox" 
                                            :value="facility.id" 
                                            v-model="selectedFacilities"
                                            class="peer w-4 h-4 border-slate-300 rounded text-teal-600 focus:ring-teal-500 cursor-pointer"
                                        />
                                    </div>
                                    <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900 transition-colors leading-tight">{{ facility.name }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Apply Filter Buttons -->
                        <div class="pt-4 flex items-center gap-2">
                            <Button 
                                @click="minPrice = null; maxPrice = null; selectedFacilities = []; fetchKosFromServer()" 
                                variant="outline" 
                                class="flex-shrink-0 text-xs font-bold border-slate-200 text-slate-500"
                            >
                                Reset
                            </Button>
                            <Button 
                                @click="fetchKosFromServer(); isSettingsOpen = false;" 
                                class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm shadow-md transition-all hover:-translate-y-0.5"
                            >
                                Terapkan Filter
                            </Button>
                        </div>
                    </div>
                </div>

                <div v-if="latitude" class="text-xs text-center text-slate-600 bg-slate-50 p-3 rounded-xl font-medium border border-slate-100 shadow-inner mt-2">
                    <span class="font-bold text-teal-600 text-sm">{{ filteredKos.length }}</span> properti kos ditemukan
                </div>
            </div>

            <div ref="mapContainer" class="w-full h-full z-0"></div>
            </div>
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
.custom-cluster-icon {
    background: transparent;
    border: none;
}
</style>
