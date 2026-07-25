<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Flag, FileWarning, MessageSquare, CheckCircle2, Clock } from 'lucide-vue-next';
import { format } from 'date-fns';
import { id as localeID } from 'date-fns/locale';

const props = defineProps({
    reports: {
        type: Object,
        required: true,
    }
});

const getCategoryLabel = (category) => {
    const labels = {
        'data_kos_tidak_valid': 'Data Kos Tidak Valid',
        'kontak_tidak_valid': 'Kontak Tidak Valid',
        'foto_tidak_sesuai': 'Foto Tidak Sesuai',
        'lainnya': 'Lain-lain',
    };
    return labels[category] || category;
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'menunggu':
            return { label: 'Menunggu', class: 'bg-amber-100 text-amber-700 hover:bg-amber-100', icon: Clock };
        case 'diproses':
            return { label: 'Diproses', class: 'bg-blue-100 text-blue-700 hover:bg-blue-100', icon: Clock };
        case 'selesai':
            return { label: 'Selesai', class: 'bg-emerald-100 text-emerald-700 hover:bg-emerald-100', icon: CheckCircle2 };
        case 'ditolak':
            return { label: 'Ditolak', class: 'bg-red-100 text-red-700 hover:bg-red-100', icon: FileWarning };
        default:
            return { label: status, class: 'bg-slate-100 text-slate-700', icon: Flag };
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return format(new Date(dateString), 'dd MMM yyyy, HH:mm', { locale: localeID });
};
</script>

<template>
    <Head title="Ulasan & Pengaduan" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold leading-tight text-slate-900 tracking-tight">Ulasan & Pengaduan</h2>
                    <p class="mt-1 text-sm text-slate-500">Lihat dan tanggapi pengaduan dari penyewa terkait properti kos Anda.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <!-- If No Reports -->
                <div v-if="!reports.data.length" class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white py-20 px-4 text-center shadow-sm">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-teal-50 mb-4">
                        <CheckCircle2 class="h-8 w-8 text-teal-500" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Belum Ada Pengaduan</h3>
                    <p class="mt-2 max-w-sm text-sm text-slate-500">Hebat! Properti kos Anda berjalan dengan sangat baik dan belum ada komplain dari penyewa.</p>
                </div>

                <!-- Reports Grid -->
                <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="report in reports.data" :key="report.id" class="flex flex-col overflow-hidden hover:shadow-md transition-shadow duration-300">
                        <CardHeader class="border-b border-slate-100 bg-slate-50/50 pb-4">
                            <div class="flex items-start justify-between">
                                <Badge variant="outline" class="bg-white border-slate-200 text-slate-600 font-semibold shadow-sm">
                                    {{ getCategoryLabel(report.category) }}
                                </Badge>
                                <Badge :class="getStatusBadge(report.status).class" class="border-0 shadow-none font-bold">
                                    <component :is="getStatusBadge(report.status).icon" class="w-3 h-3 mr-1" />
                                    {{ getStatusBadge(report.status).label }}
                                </Badge>
                            </div>
                            <CardTitle class="mt-4 text-lg font-bold text-slate-900 line-clamp-1">
                                {{ report.boarding_house?.name || 'Properti Tidak Diketahui' }}
                            </CardTitle>
                            <CardDescription class="text-xs font-medium text-slate-500">
                                Dilaporkan oleh: <span class="text-slate-700">{{ report.reporter?.name || 'Anonim' }}</span>
                            </CardDescription>
                        </CardHeader>
                        
                        <CardContent class="flex-1 p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-2 text-sm font-semibold text-slate-700">
                                    <MessageSquare class="w-4 h-4 text-teal-600" /> Deskripsi Keluhan
                                </div>
                                <p class="text-sm text-slate-600 leading-relaxed line-clamp-3 bg-slate-50 p-3 rounded-lg border border-slate-100">
                                    "{{ report.description }}"
                                </p>
                            </div>
                            
                            <div class="mt-6 flex items-center justify-between text-xs text-slate-400 border-t pt-4 border-slate-100">
                                <span>{{ formatDate(report.created_at) }}</span>
                                <Link :href="route('owner.kos.show', report.boarding_house_id)" class="font-semibold text-teal-600 hover:text-teal-700 transition-colors">
                                    Lihat Properti &rarr;
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Pagination (If needed) -->
                <div v-if="reports.links && reports.links.length > 3" class="mt-8 flex justify-center">
                    <!-- Implement basic inertia pagination UI if reports.data requires it -->
                </div>

            </div>
        </div>
    </AppLayout>
</template>
