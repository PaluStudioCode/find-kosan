<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, CheckCircle, AlertCircle, Clock, XCircle } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';

const props = defineProps({
    reports: Array,
});

const { toast } = useToast();
const showModal = ref(false);

const form = useForm({
    boarding_house_id: '',
    category: 'lainnya',
    description: '',
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    form.post(route('reports.store'), {
        onSuccess: () => {
            showModal.value = false;
            toast({ title: 'Berhasil', description: 'Laporan Anda telah dikirim dan akan segera diproses oleh Admin.' });
        }
    });
};

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
        <Head title="Laporan & Pengaduan" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Laporan & Pengaduan</h2>
                    <p class="text-gray-500 text-sm mt-1">Sampaikan kendala atau laporkan masalah kos yang tidak sesuai.</p>
                </div>
                <Button @click="openCreate"><Plus class="w-4 h-4 mr-2" /> Buat Laporan</Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Riwayat Laporan Anda</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table v-if="reports.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tanggal</TableHead>
                                <TableHead>Kategori</TableHead>
                                <TableHead>Deskripsi</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Catatan Penyelesaian</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="report in reports" :key="report.id">
                                <TableCell>{{ new Date(report.created_at).toLocaleDateString('id-ID') }}</TableCell>
                                <TableCell>{{ categoryLabel(report.category) }}</TableCell>
                                <TableCell class="max-w-xs truncate">{{ report.description }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1 font-medium capitalize" :class="statusColor(report.status)">
                                        <component :is="statusIcon(report.status)" class="w-4 h-4" />
                                        {{ report.status }}
                                    </div>
                                </TableCell>
                                <TableCell>{{ report.resolution_note || '-' }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    
                    <div v-else class="text-center py-12 text-gray-500 border rounded-lg border-dashed">
                        Belum ada laporan yang Anda buat.
                    </div>
                </CardContent>
            </Card>

            <Dialog :open="showModal" @update:open="(v) => { if(!v) showModal = false }">
                <DialogContent class="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>Buat Laporan Baru</DialogTitle>
                        <DialogDescription>Isi detail laporan dengan jelas agar admin dapat segera memprosesnya.</DialogDescription>
                    </DialogHeader>
                    
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="category">Kategori Laporan</Label>
                            <Select v-model="form.category">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="data_kos_tidak_valid">Data Kos Tidak Valid</SelectItem>
                                    <SelectItem value="kontak_tidak_valid">Kontak Tidak Valid</SelectItem>
                                    <SelectItem value="foto_tidak_sesuai">Foto Tidak Sesuai</SelectItem>
                                    <SelectItem value="lainnya">Lainnya / Masalah Sistem</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-red-500" v-if="form.errors.category">{{ form.errors.category }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="boarding_house_id">ID Kos (Opsional)</Label>
                            <Input id="boarding_house_id" type="number" v-model="form.boarding_house_id" placeholder="Masukkan ID Kos jika ada" />
                            <p class="text-xs text-red-500" v-if="form.errors.boarding_house_id">{{ form.errors.boarding_house_id }}</p>
                            <p class="text-xs text-gray-500">Biarkan kosong jika laporan bersifat umum atau masalah sistem.</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="description">Deskripsi Lengkap</Label>
                            <Textarea id="description" v-model="form.description" rows="5" placeholder="Jelaskan kendala Anda..." required />
                            <p class="text-xs text-red-500" v-if="form.errors.description">{{ form.errors.description }}</p>
                        </div>
                        
                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="showModal = false">Batal</Button>
                            <Button type="submit" :disabled="form.processing">Kirim Laporan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
