<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import StatusBadge from '@/Components/StatusBadge.vue';
import { ChevronLeft, UploadCloud, Clock, CheckCircle } from 'lucide-vue-next';
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

// We take the latest invoice (or the one that needs payment)
const activeInvoice = props.tenancy.invoices[0];

const form = useForm({
    proof_file: null
});

const uploadPayment = () => {
    form.post(route('tenant.invoices.payment', activeInvoice.id), {
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
    <AppLayout>
        <Head title="Detail Sewa & Pembayaran" />

        <div class="mb-6">
            <Link :href="route('tenant.tenancies.index')" class="text-sm text-gray-500 hover:text-gray-900 flex items-center mb-2 inline-flex">
                <ChevronLeft class="w-4 h-4 mr-1" /> Kembali ke Daftar Sewa
            </Link>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-gray-900">Detail Sewa</h2>
                <StatusBadge :status="tenancy.status" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left col: Detail Info -->
            <div class="lg:col-span-2 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Kos & Kamar</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div><p class="text-sm text-gray-500">Nama Kos</p><p class="font-medium">{{ tenancy.boarding_house?.name }}</p></div>
                            <div><p class="text-sm text-gray-500">Pemilik / Kontak</p><p class="font-medium">{{ tenancy.boarding_house?.owner?.name }} ({{ tenancy.boarding_house?.public_contact_whatsapp_number || '-' }})</p></div>
                            <div><p class="text-sm text-gray-500">Kamar</p><p class="font-medium">{{ tenancy.room?.name }} (No. {{ tenancy.room?.room_number }})</p></div>
                            <div><p class="text-sm text-gray-500">Kapasitas Dihuni</p><p class="font-medium">{{ tenancy.occupant_count }} Orang</p></div>
                            <div><p class="text-sm text-gray-500">Tanggal Masuk</p><p class="font-medium">{{ formatDate(tenancy.start_date) }}</p></div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="activeInvoice">
                    <CardHeader>
                        <CardTitle>Riwayat Pembayaran</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!activeInvoice.payments || activeInvoice.payments.length === 0" class="text-gray-500 text-sm text-center py-4 border rounded bg-gray-50 border-dashed">
                            Belum ada riwayat pembayaran untuk tagihan ini.
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="payment in activeInvoice.payments" :key="payment.id" class="flex justify-between items-center p-3 border rounded">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-full" :class="payment.status === 'diterima' ? 'bg-green-100 text-green-600' : (payment.status === 'ditolak' ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600')">
                                        <CheckCircle v-if="payment.status === 'diterima'" class="w-5 h-5" />
                                        <Clock v-else class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ formatPrice(payment.amount) }}</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(payment.payment_date) }}</p>
                                        <p v-if="payment.review_note" class="text-xs text-red-600 mt-1">Catatan: {{ payment.review_note }}</p>
                                    </div>
                                </div>
                                <StatusBadge :status="payment.status" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right col: Payment -->
            <div class="space-y-6">
                <Card v-if="activeInvoice" class="border-blue-200 shadow-md">
                    <CardHeader class="bg-blue-50 border-b border-blue-100 rounded-t-xl">
                        <CardTitle class="text-blue-900">Tagihan Aktif</CardTitle>
                        <StatusBadge :status="activeInvoice.status" class="mt-2" />
                    </CardHeader>
                    <CardContent class="pt-6 space-y-4">
                        <div class="flex justify-between border-b pb-4">
                            <div>
                                <p class="text-sm text-gray-500">Periode Sewa</p>
                                <p class="font-medium text-sm">{{ formatDate(activeInvoice.period_start) }} - {{ formatDate(activeInvoice.period_end) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Jatuh Tempo</p>
                                <p class="font-medium text-sm text-red-600">{{ formatDate(activeInvoice.due_date) }}</p>
                            </div>
                        </div>
                        <div class="text-center py-2">
                            <p class="text-sm text-gray-500 mb-1">Total Tagihan</p>
                            <p class="text-3xl font-bold text-gray-900">{{ formatPrice(activeInvoice.amount) }}</p>
                        </div>
                        
                        <div v-if="tenancy.boarding_house?.payment_instructions" class="bg-gray-50 p-3 rounded text-sm whitespace-pre-line border">
                            <strong>Instruksi Pembayaran:</strong><br/>
                            {{ tenancy.boarding_house.payment_instructions }}
                        </div>
                        
                        <div v-if="tenancy.boarding_house?.payment_qris_image_path" class="text-center">
                            <p class="text-sm font-medium mb-2">QRIS Pembayaran</p>
                            <img :src="tenancy.boarding_house.payment_qris_image_path" class="w-48 h-48 mx-auto border rounded shadow-sm object-cover" />
                        </div>

                        <div v-if="['belum_dibayar', 'ditolak', 'jatuh_tempo'].includes(activeInvoice.status)">
                            <form @submit.prevent="uploadPayment" class="mt-4 pt-4 border-t space-y-4">
                                <p class="text-sm font-semibold">Sudah membayar?</p>
                                <div>
                                    <Label for="proof">Unggah Bukti Bayar</Label>
                                    <Input id="proof" type="file" accept="image/*" @change="e => form.proof_file = e.target.files[0]" required />
                                </div>
                                <Button type="submit" class="w-full" :disabled="form.processing || !form.proof_file">
                                    <UploadCloud class="w-4 h-4 mr-2" /> Kirim Bukti
                                </Button>
                            </form>
                        </div>
                        <div v-else-if="activeInvoice.status === 'menunggu_konfirmasi'" class="mt-4 pt-4 border-t text-center">
                            <p class="text-orange-600 font-medium text-sm flex items-center justify-center gap-2">
                                <Clock class="w-4 h-4" /> Bukti bayar sedang diperiksa pemilik.
                            </p>
                        </div>
                        <div v-else-if="activeInvoice.status === 'lunas'" class="mt-4 pt-4 border-t text-center">
                            <p class="text-green-600 font-medium text-sm flex items-center justify-center gap-2">
                                <CheckCircle class="w-4 h-4" /> Tagihan Lunas
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
