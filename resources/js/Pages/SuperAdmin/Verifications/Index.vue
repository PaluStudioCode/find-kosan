<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Deferred } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import StatusBadge from '@/components/StatusBadge.vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

const props = defineProps({
    verifications: Object,
    filters: Object
});

const currentStatus = ref(props.filters?.status || 'menunggu_verifikasi');

watch(currentStatus, (newStatus) => {
    router.get(route('superadmin.verifications.index'), { status: newStatus }, { preserveState: true, preserveScroll: true });
});

const formatDate = (date) => {
    return format(new Date(date), 'dd MMM yyyy, HH:mm', { locale: id });
};
</script>

<template>
    <AppLayout>
        <Head title="Verifikasi Kos" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Verifikasi Kos & Properti</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-1">Tinjau dan setujui pendaftaran properti baru atau perubahan data (Shadow Revision) dari pemilik kos.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-4 mb-6">
            <div class="flex items-center gap-4 border-b border-gray-200 dark:border-slate-800 pb-4 overflow-x-auto whitespace-nowrap">
                <button @click="currentStatus = 'menunggu_verifikasi'" class="px-4 py-2 rounded-full text-sm font-medium transition-colors" :class="currentStatus === 'menunggu_verifikasi' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-400' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'">Menunggu Verifikasi</button>
                <button @click="currentStatus = 'revisi'" class="px-4 py-2 rounded-full text-sm font-medium transition-colors" :class="currentStatus === 'revisi' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'">Revisi Data</button>
                <button @click="currentStatus = 'dipublikasikan'" class="px-4 py-2 rounded-full text-sm font-medium transition-colors" :class="currentStatus === 'dipublikasikan' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'">Dipublikasikan</button>
                <button @click="currentStatus = 'ditolak'" class="px-4 py-2 rounded-full text-sm font-medium transition-colors" :class="currentStatus === 'ditolak' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'">Ditolak</button>
                <button @click="currentStatus = 'nonaktif'" class="px-4 py-2 rounded-full text-sm font-medium transition-colors" :class="currentStatus === 'nonaktif' ? 'bg-red-200 text-red-900 dark:bg-red-950/70 dark:text-red-500' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'">Nonaktif (Sanksi)</button>
                <button @click="currentStatus = 'all'" class="px-4 py-2 rounded-full text-sm font-medium transition-colors" :class="currentStatus === 'all' ? 'bg-gray-900 text-white dark:bg-white dark:text-slate-900' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'">Semua</button>
            </div>

            <div class="mt-4">
                <Deferred :data="['verifications']">
                    <template #fallback>
                        <Table>
                            <TableHeader>
                                <TableRow class="dark:border-slate-800">
                                    <TableHead class="dark:text-slate-400">Nama Kos</TableHead>
                                    <TableHead class="dark:text-slate-400">Pemilik</TableHead>
                                    <TableHead class="dark:text-slate-400">Tgl Pengajuan</TableHead>
                                    <TableHead class="dark:text-slate-400">Tipe Pengajuan</TableHead>
                                    <TableHead class="text-right dark:text-slate-400">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody class="animate-pulse">
                                <TableRow v-for="n in 10" :key="n" class="dark:border-slate-800">
                                    <TableCell>
                                        <div class="space-y-2">
                                            <div class="h-5 w-32 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                            <div class="h-3 w-48 bg-slate-100 dark:bg-slate-800/50 rounded"></div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="space-y-2">
                                            <div class="h-5 w-24 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                            <div class="h-3 w-32 bg-slate-100 dark:bg-slate-800/50 rounded"></div>
                                        </div>
                                    </TableCell>
                                    <TableCell><div class="w-24 h-5 bg-slate-200 dark:bg-slate-800 rounded"></div></TableCell>
                                    <TableCell><div class="w-32 h-6 bg-slate-200 dark:bg-slate-800 rounded-full"></div></TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end"><div class="w-20 h-8 bg-slate-200 dark:bg-slate-800 rounded"></div></div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </template>
                    
                <Table v-if="verifications.data.length > 0">
                    <TableHeader>
                        <TableRow class="dark:border-slate-800">
                            <TableHead class="dark:text-slate-400">Nama Kos</TableHead>
                            <TableHead class="dark:text-slate-400">Pemilik</TableHead>
                            <TableHead class="dark:text-slate-400">Tgl Pengajuan</TableHead>
                            <TableHead class="dark:text-slate-400">Tipe Pengajuan</TableHead>
                            <TableHead class="text-right dark:text-slate-400">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="kos in verifications.data" :key="kos.id" class="dark:border-slate-800 dark:hover:bg-slate-800/50">
                            <TableCell class="font-medium dark:text-slate-200">
                                {{ kos.name }}
                                <div class="text-xs text-gray-500 dark:text-slate-500 mt-1">{{ kos.city || kos.address }}</div>
                            </TableCell>
                            <TableCell class="dark:text-slate-300">
                                {{ kos.admin?.name }}
                                <div class="text-xs text-gray-500 dark:text-slate-500 mt-1">{{ kos.admin?.email }}</div>
                            </TableCell>
                            <TableCell class="dark:text-slate-400">{{ formatDate(kos.updated_at) }}</TableCell>
                            <TableCell>
                                <span v-if="kos.pending_revisions" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400">
                                    Revisi Data
                                </span>
                                <span v-else class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-400">
                                    Kos Baru
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <Link :href="route('superadmin.verifications.show', kos.id)">
                                    <Button size="sm" :variant="(kos.status === 'menunggu_verifikasi' || kos.pending_revisions) ? 'default' : 'secondary'">
                                        {{ (kos.status === 'menunggu_verifikasi' || kos.pending_revisions) ? 'Tinjau' : 'Detail' }}
                                    </Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                
                <EmptyState 
                    v-else
                    title="Tidak ada data"
                    description="Tidak ada data kos dengan status tersebut saat ini."
                />

                </Deferred>
                <Pagination :links="verifications ? verifications.links : []" />
            </div>
        </div>
    </AppLayout>
</template>
