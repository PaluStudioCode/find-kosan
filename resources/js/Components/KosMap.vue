<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { Locate } from 'lucide-vue-next';

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    },
    center: {
        type: Object,
        default: () => ({ lat: -6.200000, lng: 106.816666 })
    }
});

const emit = defineEmits(['marker-click']);

const mapContainer = ref(null);
let map = null;
let markers = [];

const requestGPSLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                if (map) {
                    map.setView([lat, lng], 14);
                    // Add a small blue circle for user location
                    L.circleMarker([lat, lng], {
                        radius: 8,
                        fillColor: "#3b82f6",
                        color: "#ffffff",
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.9
                    }).addTo(map).bindPopup("Lokasi Anda Saat Ini");
                }
            },
            (error) => {
                alert("Sistem mewajibkan akses lokasi (GPS) untuk mencari kos di sekitar Anda. Harap aktifkan izin lokasi di browser Anda.");
            }
        );
    } else {
        alert("Browser Anda tidak mendukung fitur lokasi GPS.");
    }
};

onMounted(() => {
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });

    map = L.map(mapContainer.value).setView([props.center.lat, props.center.lng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    renderMarkers();

    // Auto request GPS when map loads
    requestGPSLocation();
});

const renderMarkers = () => {
    if (!map) return;
    
    markers.forEach(m => map.removeLayer(m));
    markers = [];

    props.items.forEach(item => {
        if (item.latitude && item.longitude) {
            const marker = L.marker([item.latitude, item.longitude])
                .addTo(map)
                .bindPopup(`<b>${item.name}</b><br>${item.address}`)
                .on('click', () => emit('marker-click', item));
            
            markers.push(marker);
        }
    });

    if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
};

watch(() => props.items, renderMarkers, { deep: true });

onBeforeUnmount(() => {
    if (map) map.remove();
});
</script>

<template>
    <div class="relative w-full">
        <div ref="mapContainer" class="w-full h-[400px] md:h-[600px] rounded-md border" style="z-index: 10;"></div>
        <button @click.prevent="requestGPSLocation" type="button" class="absolute top-4 right-4 z-[400] bg-white px-3 py-2 rounded-md shadow-md border border-gray-200 hover:bg-gray-50 flex items-center gap-2 text-sm font-medium text-gray-700 transition-colors">
            <Locate class="w-4 h-4 text-primary" /> Lokasi Saya
        </button>
    </div>
</template>
