<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import StatusBadge from '@/Components/StatusBadge.vue';
import { ChevronLeft, UploadCloud, Clock, CheckCircle, Home, User, Users, Calendar, Hash, Maximize2, Download } from 'lucide-vue-next';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { useToast } from '@/components/ui/toast/use-toast';

const props = defineProps({
    tenancy: Object
});

const { toast } = useToast();

const formatDate = (date) => {
    return format(new Date(date), 'dd MMM yyyy', { locale: id });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
};

const form = useForm({
    proof_file: null
});

const activeInvoice = computed(() => {
    if (!props.tenancy.invoices) return null;
    return props.tenancy.invoices.find(i => ['belum_dibayar', 'ditolak', 'jatuh_tempo', 'menunggu_konfirmasi'].includes(i.status));
});

const pastInvoices = computed(() => {
    if (!props.tenancy.invoices) return [];
    return props.tenancy.invoices.filter(i => i.status === 'lunas');
});

const isInitialPayment = computed(() => props.tenancy.status === 'nonaktif');

const uploadPayment = () => {
    if (!activeInvoice.value) return;
    
    form.post(route('tenant.invoices.payment', activeInvoice.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast({ title: 'Berhasil', description: 'Bukti bayar diunggah. Menunggu konfirmasi pemilik.' });
            form.reset('proof_file');
        },
        onError: (err) => {
            if (err.proof_file) toast({ title: 'Gagal', description: err.proof_file, variant: 'destructive' });
        }
    });
};
</script>

<template>
    <PublicLayout>
        <Head title="Detail Sewa & Pembayaran" />
        
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            
            <!-- Header Section -->
            <div>
                <Link :href="route('tenant.tenancies.index')" class="text-sm text-gray-500 hover:text-indigo-600 flex items-center mb-3 w-fit transition-colors">
                    <ChevronLeft class="w-4 h-4 mr-1" /> Kembali ke Daftar Sewa
                </Link>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h2 class="text-2xl font-extrabold text-gray-900">Detail Sewa & Pembayaran</h2>
                    <StatusBadge :status="tenancy.status" class="w-fit" />
                </div>
            </div>

            <!-- Informasi Kos & Kamar -->
            <Card class="border-gray-100 shadow-sm overflow-hidden">
                <div class="bg-gray-50/50 p-4 border-b border-gray-100 flex items-center gap-2">
                    <Home class="w-5 h-5 text-indigo-500" />
                    <h3 class="font-semibold text-gray-900">Informasi Properti</h3>
                </div>
                <CardContent class="p-0">
                    <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5 mb-1"><Home class="w-3.5 h-3.5" /> Nama Kos</p>
                                <p class="font-semibold text-gray-900">{{ tenancy.boarding_house?.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5 mb-1"><User class="w-3.5 h-3.5" /> Pemilik / Kontak</p>
                                <p class="font-medium text-gray-900">{{ tenancy.boarding_house?.owner?.name }}</p>
                                <p class="text-sm text-gray-600">{{ tenancy.boarding_house?.public_contact_whatsapp_number || '-' }}</p>
                            </div>
                        </div>
                        <div class="p-5 space-y-4 bg-gray-50/30">
                            <div>
                                <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5 mb-1"><Hash class="w-3.5 h-3.5" /> Kamar</p>
                                <p class="font-semibold text-gray-900">{{ tenancy.room?.name }} (No. {{ tenancy.room?.room_number }})</p>
                            </div>
                            <div class="flex justify-between gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5 mb-1"><Users class="w-3.5 h-3.5" /> Penghuni</p>
                                    <p class="font-medium text-gray-900">{{ tenancy.occupant_count }} Orang</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5 mb-1"><Calendar class="w-3.5 h-3.5" /> Masuk</p>
                                    <p class="font-medium text-gray-900">{{ formatDate(tenancy.start_date) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Payment Form Section (Active Invoice) -->
            <Card v-if="activeInvoice" class="border-indigo-200 shadow-md overflow-hidden relative">
                <!-- Highlight line -->
                <div class="absolute top-0 left-0 w-full h-1 bg-indigo-500"></div>
                
                <CardHeader class="bg-indigo-50/50 pb-4">
                    <CardTitle class="text-indigo-900 text-lg flex justify-between items-center">
                        <span>{{ isInitialPayment ? 'Pembayaran Sewa Pertama' : 'Tagihan Pembayaran' }}</span>
                        <StatusBadge v-if="!isInitialPayment" :status="activeInvoice.status" />
                        <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">Wajib Dibayar</span>
                    </CardTitle>
                    <p class="text-sm text-indigo-700 mt-1 font-medium">Periode: {{ formatDate(activeInvoice.period_start) }} - {{ formatDate(activeInvoice.period_end) }}</p>
                </CardHeader>
                
                <CardContent class="pt-6 space-y-6">
                    <!-- Amount Display -->
                    <div class="text-center bg-gray-50 rounded-xl py-6 border border-gray-100">
                        <p class="text-sm text-gray-500 mb-1 font-medium uppercase tracking-wider">Total Pembayaran</p>
                        <p class="text-4xl font-extrabold text-gray-900 tracking-tight">{{ formatPrice(activeInvoice.amount) }}</p>
                        <p class="text-sm text-red-600 mt-3 font-medium bg-red-50 inline-block px-3 py-1 rounded-full border border-red-100" v-if="['belum_dibayar', 'ditolak', 'jatuh_tempo'].includes(activeInvoice.status)">
                            {{ isInitialPayment ? 'Batas Waktu: ' : 'Jatuh Tempo: ' }} {{ formatDate(activeInvoice.due_date) }}
                        </p>
                    </div>
                    
                    <div v-if="['belum_dibayar', 'ditolak', 'jatuh_tempo'].includes(activeInvoice.status)" class="space-y-6">
                        <!-- Last rejected note if any -->
                        <div v-if="activeInvoice.status === 'ditolak' && activeInvoice.payments && activeInvoice.payments.length > 0" class="bg-red-50 p-4 border border-red-200 rounded-lg">
                            <p class="text-sm font-bold text-red-800 mb-1 flex items-center gap-2">
                                <span class="flex w-5 h-5 rounded-full bg-red-100 items-center justify-center">!</span>
                                Pembayaran Sebelumnya Ditolak
                            </p>
                            <p class="text-sm text-red-700 ml-7">{{ activeInvoice.payments[activeInvoice.payments.length-1].review_note || 'Mohon unggah bukti pembayaran yang lebih jelas atau valid.' }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Instructions & QRIS -->
                            <div class="space-y-4">
                                <div v-if="tenancy.boarding_house?.payment_instructions" class="bg-indigo-50/50 p-4 rounded-lg text-sm whitespace-pre-line border border-indigo-100/50 text-indigo-900">
                                    <strong class="block mb-2 text-indigo-950">Instruksi Pembayaran:</strong>
                                    {{ tenancy.boarding_house.payment_instructions }}
                                </div>
                                
                                <div v-if="tenancy.boarding_house?.payment_qris_image_path" class="text-center p-4 border rounded-lg bg-white shadow-sm">
                                    <p class="text-sm font-bold mb-3 text-gray-700">QRIS Tersedia</p>
                                    
                                    <Dialog>
                                        <DialogTrigger as-child>
                                            <div class="relative group cursor-pointer inline-block bg-gray-50 p-2 rounded-lg border">
                                                <img :src="tenancy.boarding_house.payment_qris_image_path" alt="QRIS" class="w-40 h-40 object-contain rounded bg-white" />
                                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded flex flex-col items-center justify-center gap-2">
                                                    <Maximize2 class="w-6 h-6 text-white" />
                                                    <span class="text-white text-xs font-medium">Perbesar</span>
                                                </div>
                                            </div>
                                        </DialogTrigger>
                                        <DialogContent class="sm:max-w-md flex flex-col items-center p-6">
                                            <DialogHeader>
                                                <DialogTitle class="text-center">Scan QRIS Pembayaran</DialogTitle>
                                            </DialogHeader>
                                            <div class="bg-gray-50 p-3 rounded-xl border mt-4 mb-6">
                                                <img :src="tenancy.boarding_house.payment_qris_image_path" alt="QRIS" class="w-64 h-64 object-contain rounded bg-white shadow-sm border p-2" />
                                            </div>
                                            
                                            <a :href="tenancy.boarding_house.payment_qris_image_path" download class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium transition-colors w-full justify-center">
                                                <Download class="w-4 h-4" /> Unduh QRIS
                                            </a>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </div>

                            <!-- Upload Form -->
                            <div class="bg-gray-50 border rounded-xl p-5">
                                <h4 class="font-bold text-gray-900 mb-4">Konfirmasi Pembayaran</h4>
                                <form @submit.prevent="uploadPayment" class="space-y-5">
                                    <div>
                                        <Label for="proof" class="mb-2 block">Unggah Bukti Transfer / Resi</Label>
                                        <Input id="proof" type="file" accept="image/*" @change="e => form.proof_file = e.target.files[0]" required class="bg-white cursor-pointer" />
                                        <p class="text-xs text-gray-500 mt-2">Format: JPG, PNG. Maksimal 2MB.</p>
                                    </div>
                                    <Button type="submit" class="w-full h-11 bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-sm transition-all" :disabled="form.processing || !form.proof_file">
                                        <UploadCloud class="w-4 h-4 mr-2" /> 
                                        {{ form.processing ? 'Mengunggah...' : 'Kirim Bukti Pembayaran' }}
                                    </Button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Waiting State -->
                    <div v-else-if="activeInvoice.status === 'menunggu_konfirmasi'" class="text-center py-10 bg-orange-50/50 rounded-xl border border-orange-100">
                        <Clock class="w-16 h-16 text-orange-400 mx-auto mb-4 opacity-80" />
                        <h3 class="text-lg font-bold text-orange-900 mb-2">Menunggu Konfirmasi Pemilik</h3>
                        <p class="text-sm text-orange-700 max-w-sm mx-auto">Bukti pembayaran Anda telah diterima dan sedang diverifikasi oleh pemilik kos. Status sewa Anda akan aktif setelah dikonfirmasi.</p>
                    </div>
                </CardContent>
            </Card>
            
            <Card v-else class="bg-gradient-to-br from-emerald-50 to-teal-50 border-emerald-200 shadow-sm">
                <CardContent class="py-12 text-center space-y-3">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto shadow-sm mb-2">
                        <CheckCircle class="w-10 h-10 text-emerald-500" />
                    </div>
                    <h3 class="text-2xl font-extrabold text-emerald-900">Sewa Aktif & Lunas</h3>
                    <p class="text-emerald-700 text-sm max-w-md mx-auto">Anda tidak memiliki tagihan pembayaran yang tertunda saat ini. Tagihan periode berikutnya akan muncul secara otomatis di sini.</p>
                </CardContent>
            </Card>

            <!-- Riwayat Pembayaran -->
            <Card v-if="pastInvoices.length > 0" class="border-gray-200">
                <CardHeader class="border-b bg-gray-50/50 pb-4">
                    <CardTitle class="text-gray-800 text-lg flex items-center gap-2">
                        <Clock class="w-5 h-5 text-gray-500" /> Riwayat Transaksi
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="divide-y divide-gray-100">
                        <div v-for="invoice in pastInvoices" :key="invoice.id" class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50 transition-colors">
                            <div>
                                <p class="font-bold text-gray-900 mb-1">Periode: {{ formatDate(invoice.period_start) }} - {{ formatDate(invoice.period_end) }}</p>
                                <p class="text-sm text-gray-500 flex items-center gap-1.5">
                                    <CheckCircle class="w-4 h-4 text-emerald-500" /> 
                                    Berhasil lunas pada {{ invoice.payments && invoice.payments.length ? formatDate(invoice.payments[invoice.payments.length-1].payment_date) : '-' }}
                                </p>
                            </div>
                            <div class="flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2">
                                <p class="font-extrabold text-lg text-gray-900">{{ formatPrice(invoice.amount) }}</p>
                                <StatusBadge :status="invoice.status" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
            
        </div>
    </PublicLayout>
</template>
