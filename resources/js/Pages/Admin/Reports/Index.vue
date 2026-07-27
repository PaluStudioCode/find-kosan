<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Eye, CheckCircle, AlertCircle, Clock, XCircle, Search } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps({
    reports: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
const category = ref(props.filters?.category || 'all');

let searchTimeout = null;

watch([search, status, category], ([newSearch, newStatus, newCategory]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('admin.reports.index'),
            { search: newSearch, status: newStatus, category: newCategory },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
});

const statusIcon = (status) => {
    switch(status) {
        case 'selesai': return CheckCircle;
        case 'ditolak': return XCircle;
        case 'diproses': return Clock;
        default: return AlertCircle;
    }
};

const statusColor = (status) => {
    switch(status) {
        case 'selesai': return 'text-green-600 dark:text-green-400';
        case 'ditolak': return 'text-red-600 dark:text-red-400';
        case 'diproses': return 'text-blue-600 dark:text-blue-400';
        default: return 'text-yellow-600 dark:text-yellow-400';
    }
};

const categoryLabel = (cat) => {
    const labels = {
        'data_kos_tidak_valid': 'Data Kos Tidak Valid',
        'kontak_tidak_valid': 'Kontak Tidak Valid',
        'foto_tidak_sesuai': 'Foto Tidak Sesuai',
        'lainnya': 'Lainnya'
    };
    return labels[cat] || cat;
};
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Laporan" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Laporan</h2>
                    <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">Daftar keluhan dan laporan dari pengguna.</p>
                </div>
            </div>

            <Card class="border-0 dark:bg-slate-900 shadow-sm">
                <CardHeader class="border-b dark:border-slate-800">
                    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-4">
                        <CardTitle class="dark:text-white">Daftar Laporan Masuk</CardTitle>
                        
                        <div class="flex flex-col sm:flex-row items-center gap-3 w-full xl:w-auto">
                            <div class="relative w-full sm:w-64">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500 dark:text-slate-400" />
                                <Input v-model="search" placeholder="Cari kos, pelapor..." class="pl-9 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500" />
                            </div>
                            <Select v-model="status">
                                <SelectTrigger class="w-full sm:w-40 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200"><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent class="dark:bg-slate-800 dark:border-slate-700">
                                    <SelectItem value="all" class="dark:text-slate-200 dark:focus:bg-slate-700">Semua Status</SelectItem>
                                    <SelectItem value="menunggu" class="dark:text-slate-200 dark:focus:bg-slate-700">Menunggu</SelectItem>
                                    <SelectItem value="selesai" class="dark:text-slate-200 dark:focus:bg-slate-700">Selesai</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="category">
                                <SelectTrigger class="w-full sm:w-48 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200"><SelectValue placeholder="Kategori" /></SelectTrigger>
                                <SelectContent class="dark:bg-slate-800 dark:border-slate-700">
                                    <SelectItem value="all" class="dark:text-slate-200 dark:focus:bg-slate-700">Semua Kategori</SelectItem>
                                    <SelectItem value="data_kos_tidak_valid" class="dark:text-slate-200 dark:focus:bg-slate-700">Data Tidak Valid</SelectItem>
                                    <SelectItem value="kontak_tidak_valid" class="dark:text-slate-200 dark:focus:bg-slate-700">Kontak Tidak Valid</SelectItem>
                                    <SelectItem value="foto_tidak_sesuai" class="dark:text-slate-200 dark:focus:bg-slate-700">Foto Tidak Sesuai</SelectItem>
                                    <SelectItem value="lainnya" class="dark:text-slate-200 dark:focus:bg-slate-700">Lainnya</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0 border-0">
                    <Table v-if="reports.data.length > 0">
                        <TableHeader>
                            <TableRow class="dark:border-slate-800 bg-gray-50 dark:bg-slate-800/50">
                                <TableHead class="dark:text-slate-400">Tgl Lapor</TableHead>
                                <TableHead class="dark:text-slate-400">Pelapor</TableHead>
                                <TableHead class="dark:text-slate-400">Terlapor (Kos & Owner)</TableHead>
                                <TableHead class="dark:text-slate-400">Kategori</TableHead>
                                <TableHead class="dark:text-slate-400">Status</TableHead>
                                <TableHead class="text-right dark:text-slate-400">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="report in reports.data" :key="report.id" class="dark:border-slate-800 dark:hover:bg-slate-800/30 transition-colors">
                                <TableCell class="dark:text-slate-400">{{ new Date(report.created_at).toLocaleDateString('id-ID') }}</TableCell>
                                <TableCell>
                                    <div class="font-medium dark:text-slate-200">{{ report.reporter?.name || 'Anonim' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-500 capitalize">{{ report.reporter?.role?.replace('_', ' ') || '-' }}</div>
                                </TableCell>
                                <TableCell>
                                    <div class="font-medium text-primary dark:text-blue-400">{{ report.boarding_house?.name || 'Kos Tidak Diketahui' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-500">Pemilik: {{ report.boarding_house?.owner?.name || 'Tidak Ada Data' }}</div>
                                </TableCell>
                                <TableCell>
                                    <span class="inline-flex px-2 py-1 bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 text-xs rounded-md font-medium border dark:border-slate-700">
                                        {{ categoryLabel(report.category) }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1.5 font-medium capitalize text-sm" :class="statusColor(report.status)">
                                        <component :is="statusIcon(report.status)" class="w-4 h-4" />
                                        {{ report.status }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Link :href="route('admin.reports.show', report.id)">
                                        <Button variant="outline" size="sm" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                            <Eye class="w-4 h-4 mr-2" /> Detail
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    
                    <div v-else class="text-center py-12 text-gray-500 dark:text-slate-500 border-b dark:border-slate-800 rounded-b-lg">
                        Belum ada laporan masuk.
                    </div>

                    <!-- Pagination -->
                    <div class="p-4 flex justify-center bg-gray-50 dark:bg-slate-900 rounded-b-lg border-t dark:border-slate-800" v-if="reports.links && reports.links.length > 3">
                        <div class="flex gap-1">
                            <template v-for="(link, i) in reports.links" :key="i">
                                <Link 
                                    v-if="link.url"
                                    :href="link.url" 
                                    class="px-3 py-1 border rounded-md text-sm transition-colors"
                                    :class="link.active ? 'bg-primary text-white border-primary dark:bg-blue-600 dark:text-white dark:border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700'"
                                    v-html="link.label"
                                />
                                <span v-else class="px-3 py-1 border rounded-md text-sm text-gray-400 dark:text-slate-500 bg-gray-50 dark:bg-slate-800 dark:border-slate-700" v-html="link.label"></span>
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
