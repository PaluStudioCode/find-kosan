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
    router.get(route('admin.verifications.index'), { status: newStatus }, { preserveState: true, preserveScroll: true });
});

const formatDate = (date) => {
    return format(new Date(date), 'dd MMM yyyy, HH:mm', { locale: id });
};
</script>

<template>
    <AppLayout>
        <Head title="Verifikasi Kos" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Verifikasi Kos & Properti</h2>
            <p class="text-gray-500 mt-1">Tinjau dan setujui pendaftaran properti baru atau perubahan data (Shadow Revision) dari pemilik kos.</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
            <div class="flex items-center gap-4 border-b pb-4">
                <button @click="currentStatus = 'menunggu_verifikasi'" class="px-4 py-2 rounded-full text-sm font-medium transition-colors" :class="currentStatus === 'menunggu_verifikasi' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Menunggu Verifikasi</button>
                <button @click="currentStatus = 'dipublikasikan'" class="px-4 py-2 rounded-full text-sm font-medium transition-colors" :class="currentStatus === 'dipublikasikan' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Dipublikasikan</button>
                <button @click="currentStatus = 'ditolak'" class="px-4 py-2 rounded-full text-sm font-medium transition-colors" :class="currentStatus === 'ditolak' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Ditolak</button>
                <button @click="currentStatus = 'all'" class="px-4 py-2 rounded-full text-sm font-medium transition-colors" :class="currentStatus === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Semua</button>
            </div>

            <div class="mt-4">
                <Table v-if="verifications.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nama Kos</TableHead>
                            <TableHead>Pemilik</TableHead>
                            <TableHead>Tgl Pengajuan</TableHead>
                            <TableHead>Tipe Pengajuan</TableHead>
                            <TableHead class="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="kos in verifications.data" :key="kos.id">
                            <TableCell class="font-medium">
                                {{ kos.name }}
                                <div class="text-xs text-gray-500 mt-1">{{ kos.city || kos.address }}</div>
                            </TableCell>
                            <TableCell>
                                {{ kos.owner?.name }}
                                <div class="text-xs text-gray-500 mt-1">{{ kos.owner?.email }}</div>
                            </TableCell>
                            <TableCell>{{ formatDate(kos.updated_at) }}</TableCell>
                            <TableCell>
                                <span v-if="kos.pending_revisions && kos.status === 'menunggu_verifikasi'" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    Revisi Data
                                </span>
                                <span v-else class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    Kos Baru
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <Link :href="route('admin.verifications.show', kos.id)">
                                    <Button size="sm" :variant="kos.status === 'menunggu_verifikasi' ? 'default' : 'secondary'">
                                        {{ kos.status === 'menunggu_verifikasi' ? 'Tinjau' : 'Detail' }}
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

                <div v-if="verifications.links && verifications.links.length > 3" class="mt-6 flex justify-center gap-1 border-t pt-4">
                    <template v-for="(link, k) in verifications.links" :key="k">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm border rounded text-gray-400 bg-white" v-html="link.label" />
                        <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-gray-100 bg-white" :class="{ 'bg-gray-900 text-white hover:bg-gray-800 border-gray-900': link.active }" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
