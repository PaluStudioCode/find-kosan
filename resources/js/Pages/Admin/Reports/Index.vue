<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Eye, CheckCircle, AlertCircle, Clock, XCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps({
    reports: Object,
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
        case 'selesai': return 'text-green-600';
        case 'ditolak': return 'text-red-600';
        case 'diproses': return 'text-blue-600';
        default: return 'text-yellow-600';
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
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen Laporan</h2>
                    <p class="text-gray-500 text-sm mt-1">Daftar keluhan dan laporan dari pengguna.</p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Daftar Laporan Masuk</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table v-if="reports.data.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tgl Lapor</TableHead>
                                <TableHead>Pelapor</TableHead>
                                <TableHead>Kategori</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="report in reports.data" :key="report.id">
                                <TableCell>{{ new Date(report.created_at).toLocaleDateString('id-ID') }}</TableCell>
                                <TableCell>
                                    <div class="font-medium">{{ report.reporter?.name }}</div>
                                    <div class="text-xs text-gray-500 capitalize">{{ report.reporter?.role.replace('_', ' ') }}</div>
                                </TableCell>
                                <TableCell>{{ categoryLabel(report.category) }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1 font-medium capitalize" :class="statusColor(report.status)">
                                        <component :is="statusIcon(report.status)" class="w-4 h-4" />
                                        {{ report.status }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Link :href="route('admin.reports.show', report.id)">
                                        <Button variant="outline" size="sm">
                                            <Eye class="w-4 h-4 mr-2" /> Detail
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    
                    <div v-else class="text-center py-12 text-gray-500 border rounded-lg border-dashed">
                        Belum ada laporan masuk.
                    </div>

                    <div class="mt-4 flex justify-center" v-if="reports.links && reports.links.length > 3">
                        <div class="flex gap-1">
                            <template v-for="(link, i) in reports.links" :key="i">
                                <Link 
                                    v-if="link.url"
                                    :href="link.url" 
                                    class="px-3 py-1 border rounded-md text-sm"
                                    :class="link.active ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                    v-html="link.label"
                                />
                                <span v-else class="px-3 py-1 border rounded-md text-sm text-gray-400 bg-gray-50" v-html="link.label"></span>
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
