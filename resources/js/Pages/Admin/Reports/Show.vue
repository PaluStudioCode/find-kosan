<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/toast/use-toast';
import { ChevronLeft, Info, CheckCircle2 } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    report: Object,
});

const { toast } = useToast();

const form = useForm({
    status: props.report.status,
    resolution_note: props.report.resolution_note || '',
});

const submit = () => {
    form.put(route('admin.reports.update', props.report.id), {
        onSuccess: () => {
            toast({ title: 'Berhasil', description: 'Status laporan diperbarui.' });
        }
    });
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
        <Head :title="'Laporan #' + report.id" />

        <div class="space-y-6">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.reports.index')">
                    <Button variant="outline" size="icon"><ChevronLeft class="w-4 h-4" /></Button>
                </Link>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Detail Laporan #{{ report.id }}</h2>
                    <p class="text-gray-500 text-sm mt-1">Dibuat pada {{ new Date(report.created_at).toLocaleDateString('id-ID') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Report Info -->
                <div class="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Isi Laporan</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 mb-1">Kategori</h3>
                                <p class="text-base font-medium">{{ categoryLabel(report.category) }}</p>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-500 mb-1">Deskripsi</h3>
                                <p class="text-gray-700 whitespace-pre-wrap">{{ report.description }}</p>
                            </div>
                            <div v-if="report.boarding_house">
                                <h3 class="text-sm font-semibold text-gray-500 mb-1">Terkait Kos</h3>
                                <div class="p-3 bg-gray-50 rounded-md border flex items-center justify-between">
                                    <span class="font-medium">{{ report.boarding_house.name }}</span>
                                    <!-- Link to view Kos detail in public -->
                                    <a :href="route('kos.show', report.boarding_house.slug)" target="_blank" class="text-primary text-sm hover:underline">Lihat Kos</a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card v-if="report.handled_by">
                        <CardHeader>
                            <CardTitle>Informasi Penanganan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-col sm:flex-row gap-6">
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-gray-500 mb-1">Ditangani Oleh</h3>
                                    <p class="text-base font-medium">{{ report.handler?.name || 'Sistem' }}</p>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-gray-500 mb-1">Pada Tanggal</h3>
                                    <p class="text-base font-medium">{{ new Date(report.handled_at).toLocaleString('id-ID') }}</p>
                                </div>
                            </div>
                            <div class="mt-4" v-if="report.resolution_note">
                                <h3 class="text-sm font-semibold text-gray-500 mb-1">Catatan Resolusi Akhir</h3>
                                <div class="p-3 bg-blue-50 text-blue-800 rounded-md text-sm border border-blue-200">
                                    {{ report.resolution_note }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Admin Action / Status -->
                <div>
                    <Card>
                        <CardHeader>
                            <CardTitle>Tindak Lanjut</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="mb-6 flex items-center justify-between">
                                <span class="font-medium">Status Saat Ini</span>
                                <StatusBadge :status="report.status" />
                            </div>

                            <form @submit.prevent="submit" class="space-y-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Ubah Status</label>
                                    <Select v-model="form.status">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="menunggu">Menunggu</SelectItem>
                                            <SelectItem value="diproses">Diproses</SelectItem>
                                            <SelectItem value="selesai">Selesai</SelectItem>
                                            <SelectItem value="ditolak">Ditolak</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Catatan Penanganan</label>
                                    <Textarea v-model="form.resolution_note" rows="4" placeholder="Tulis catatan solusi (akan terlihat oleh pelapor)..." />
                                </div>

                                <Button type="submit" class="w-full" :disabled="form.processing">Simpan Perubahan</Button>
                            </form>
                        </CardContent>
                    </Card>
                    
                    <Card class="mt-6">
                        <CardHeader>
                            <CardTitle>Pelapor</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center text-primary font-bold">
                                    {{ report.reporter?.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="font-medium">{{ report.reporter?.name }}</div>
                                    <div class="text-sm text-gray-500">{{ report.reporter?.email }}</div>
                                </div>
                            </div>
                            <div class="text-xs font-semibold px-2 py-1 bg-gray-100 rounded-full w-fit capitalize">
                                {{ report.reporter?.role.replace('_', ' ') }}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
