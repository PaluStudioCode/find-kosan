<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ChevronLeft } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';

// Partials
import TabInfo from './Partials/TabInfo.vue';
import TabRooms from './Partials/TabRooms.vue';
import TabPhotos from './Partials/TabPhotos.vue';
import TabLegal from './Partials/TabLegal.vue';
import TabVerification from './Partials/TabVerification.vue';

const props = defineProps({
    kos: Object,
    kosFacilitiesList: Array,
    roomFacilitiesList: Array,
});

const isLocked = computed(() => props.kos.status === 'menunggu_verifikasi');

const activeTab = ref('info');
const tabs = [
    { id: 'info', name: 'Info Utama' },
    { id: 'rooms', name: 'Manajemen Kamar' },
    { id: 'photos', name: 'Galeri & Pembayaran' },
    { id: 'legal', name: 'Dokumen Legalitas' },
    { id: 'verification', name: 'Status & Verifikasi' },
];
</script>

<template>
    <AppLayout>
        <Head :title="`Kelola - ${kos.name}`" />

        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
            <div>
                <Link :href="route('owner.kos.index')" class="text-sm text-gray-500 hover:text-gray-900 flex items-center mb-2 inline-flex">
                    <ChevronLeft class="w-4 h-4 mr-1" /> Kembali ke Daftar Kos
                </Link>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-gray-900">{{ kos.name }}</h2>
                    <StatusBadge :status="kos.status" />
                </div>
                <p v-if="kos.pending_revisions" class="text-xs text-blue-600 mt-2 bg-blue-50 px-2 py-1 rounded inline-block border border-blue-200">
                    Perhatian: Properti ini memiliki revisi data yang sedang menunggu persetujuan (Shadow Revision).
                </p>
            </div>
        </div>

        <div class="bg-white border rounded-lg shadow-sm">
            <div class="flex overflow-x-auto border-b">
                <button 
                    v-for="tab in tabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 focus:outline-none transition-colors"
                    :class="activeTab === tab.id ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                >
                    {{ tab.name }}
                </button>
            </div>
            
            <div class="p-6">
                <TabInfo v-if="activeTab === 'info'" :kos="kos" :facilities="kosFacilitiesList" :is-locked="isLocked" />
                <TabRooms v-if="activeTab === 'rooms'" :kos="kos" :facilities="roomFacilitiesList" :is-locked="isLocked" />
                <TabPhotos v-if="activeTab === 'photos'" :kos="kos" :is-locked="isLocked" />
                <TabLegal v-if="activeTab === 'legal'" :kos="kos" :is-locked="isLocked" />
                <TabVerification v-if="activeTab === 'verification'" :kos="kos" />
            </div>
        </div>
    </AppLayout>
</template>
