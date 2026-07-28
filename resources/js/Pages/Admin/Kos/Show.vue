<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ShieldAlert, ExternalLink } from 'lucide-vue-next';
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
    kosRulesList: Array,
    roomFacilitiesList: Array,
});

const isLocked = computed(() => props.kos.status === 'menunggu_verifikasi');

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'info');
const tabs = [
    { id: 'info', name: 'Info Utama' },
    { id: 'rooms', name: 'Manajemen Kamar' },
    { id: 'photos', name: 'Galeri' },
    { id: 'legal', name: 'Dokumen Legalitas' },
    { id: 'verification', name: 'Status & Verifikasi' },
];
</script>

<template>
    <AppLayout>
        <Head :title="`Kelola - ${kos.name}`" />

        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
            <div>
                <Link :href="route('admin.kos.index')" class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white flex items-center mb-2 inline-flex transition-colors">
                    <ChevronLeft class="w-4 h-4 mr-1" /> Kembali ke Daftar Kos
                </Link>
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ kos.name }}</h2>
                    <StatusBadge :status="kos.status" />
                    <span v-if="kos.status === 'nonaktif' && kos.reports && kos.reports.length > 0" class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded font-bold border border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-900/50">
                        ⚠️ CEK TAB VERIFIKASI
                    </span>
                </div>
                <p v-if="kos.pending_revisions" class="text-xs text-blue-600 mt-2 bg-blue-50 px-2 py-1 rounded inline-block border border-blue-200 dark:text-blue-400 dark:bg-blue-900/30 dark:border-blue-900/50">
                    Perhatian: Properti ini memiliki revisi data yang sedang menunggu persetujuan (Shadow Revision).
                </p>
            </div>
            
            <div class="flex gap-2 w-full md:w-auto">
                <a v-if="kos.status === 'dipublikasikan'" :href="route('public.kos.show', kos.id)" target="_blank" class="w-full md:w-auto">
                    <Button type="button" variant="outline" class="w-full md:w-auto border-teal-200 text-teal-700 bg-teal-50 hover:bg-teal-100 font-medium dark:border-teal-900/50 dark:text-teal-400 dark:bg-teal-900/20 dark:hover:bg-teal-900/40">
                        <ExternalLink class="w-4 h-4 mr-2" /> Halaman Publik
                    </Button>
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border dark:border-slate-800 rounded-lg shadow-sm">
            <div class="flex overflow-x-auto border-b dark:border-slate-800 scrollbar-hide">
                <button 
                    v-for="tab in tabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 focus:outline-none transition-colors"
                    :class="activeTab === tab.id ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300 dark:hover:border-slate-700'"
                >
                    {{ tab.name }}
                </button>
            </div>
            
            <div class="p-6">
                <TabInfo v-if="activeTab === 'info'" :kos="kos" :facilities="kosFacilitiesList" :rules="kosRulesList" :is-locked="isLocked" />
                <TabRooms v-if="activeTab === 'rooms'" :kos="kos" :facilities="roomFacilitiesList" :is-locked="isLocked" />
                <TabPhotos v-if="activeTab === 'photos'" :kos="kos" :is-locked="isLocked" />
                <TabLegal v-if="activeTab === 'legal'" :kos="kos" :is-locked="isLocked" />
                <TabVerification v-if="activeTab === 'verification'" :kos="kos" />
            </div>
        </div>
    </AppLayout>
</template>
