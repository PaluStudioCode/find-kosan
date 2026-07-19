<script setup>
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { Locate } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: Object, // { lat: number, lng: number }
        default: () => ({ lat: -6.200000, lng: 106.816666 }) // Default to Jakarta
    },
    readonly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);
const mapContainer = ref(null);
let map = null;
let marker = null;

const getCurrentLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                if (map && marker && !props.readonly) {
                    map.setView([lat, lng], 15);
                    marker.setLatLng([lat, lng]);
                    emit('update:modelValue', { lat, lng });
                }
            },
            (error) => {
                alert("Harap izinkan akses lokasi (GPS) pada browser Anda agar kami dapat mendeteksi lokasi dengan akurat.");
            }
        );
    } else {
        alert("Browser Anda tidak mendukung fitur lokasi GPS.");
    }
};

onMounted(() => {
    // Fix leaflet marker icon issue in vite
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    });

    map = L.map(mapContainer.value).setView([props.modelValue.lat, props.modelValue.lng], 15);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    marker = L.marker([props.modelValue.lat, props.modelValue.lng], {
        draggable: !props.readonly
    }).addTo(map);

    if (!props.readonly) {
        marker.on('dragend', (e) => {
            const position = e.target.getLatLng();
            emit('update:modelValue', { lat: position.lat, lng: position.lng });
        });

        map.on('click', (e) => {
            marker.setLatLng(e.latlng);
            emit('update:modelValue', { lat: e.latlng.lat, lng: e.latlng.lng });
        });

        // Auto request GPS if it's default Jakarta coordinates
        if (props.modelValue.lat === -6.200000 && props.modelValue.lng === 106.816666) {
            getCurrentLocation();
        }
    }
});

watch(() => props.modelValue, (newVal) => {
    if (marker && map && (newVal.lat !== marker.getLatLng().lat || newVal.lng !== marker.getLatLng().lng)) {
        const newLatLng = new L.LatLng(newVal.lat, newVal.lng);
        marker.setLatLng(newLatLng);
        map.panTo(newLatLng);
    }
}, { deep: true });

onBeforeUnmount(() => {
    if (map) {
        map.remove();
    }
});
</script>

<template>
    <div class="relative w-full">
        <div ref="mapContainer" class="w-full h-[300px] md:h-[400px] rounded-md border" style="z-index: 10;"></div>
        <button v-if="!readonly" @click.prevent="getCurrentLocation" type="button" class="absolute top-4 right-4 z-[400] bg-white px-3 py-2 rounded-md shadow-md border border-gray-200 hover:bg-gray-50 flex items-center gap-2 text-sm font-medium text-gray-700 transition-colors">
            <Locate class="w-4 h-4 text-primary" /> Gunakan GPS Saya
        </button>
    </div>
</template>
