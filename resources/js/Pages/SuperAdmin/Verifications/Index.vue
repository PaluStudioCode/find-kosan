<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
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

                <div v-if="verifications.links && verifications.links.length > 3" class="mt-6 flex justify-center gap-1 border-t dark:border-slate-800 pt-4">
                    <template v-for="(link, k) in verifications.links" :key="k">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm border dark:border-slate-700 rounded text-gray-400 dark:text-slate-500 bg-white dark:bg-slate-800" v-html="link.label" />
                        <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-2 text-sm border dark:border-slate-700 rounded hover:bg-gray-100 dark:hover:bg-slate-700 bg-white dark:bg-slate-800 dark:text-slate-300" :class="{ 'bg-gray-900 text-white hover:bg-gray-800 border-gray-900 dark:bg-white dark:text-slate-900 dark:border-white dark:hover:bg-gray-200': link.active }" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
