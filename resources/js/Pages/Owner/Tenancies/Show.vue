<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import StatusBadge from '@/Components/StatusBadge.vue';
import { ChevronLeft, CheckCircle, XCircle } from 'lucide-vue-next';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { useToast } from '@/components/ui/toast/use-toast';

const props = defineProps({
    tenancy: Object
});

const { toast } = useToast();

const formatDate = (date) => {
    if (!date) return '-';
    return format(new Date(date), 'dd MMM yyyy', { locale: id });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
};

const activeInvoice = props.tenancy.invoices[0];
const pendingPayment = activeInvoice?.payments?.find(p => p.status === 'menunggu_konfirmasi');

const form = useForm({
    action: 'approve',
    review_note: ''
});

const isRejecting = ref(false);

const confirmAction = (action) => {
    if (action === 'reject' && !isRejecting.value) {
        isRejecting.value = true;
        return;
    }

    form.action = action;
    form.post(route('owner.payments.confirm', pendingPayment.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast({ title: 'Berhasil', description: `Pembayaran berhasil di${action === 'approve' ? 'setujui' : 'tolak'}.` });
            isRejecting.value = false;
        }
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Detail Sewa Penyewa" />

        <div class="mb-6">
            <Link :href="route('owner.tenancies.index')" class="text-sm text-gray-500 hover:text-gray-900 flex items-center mb-2 inline-flex">
                <ChevronLeft class="w-4 h-4 mr-1" /> Kembali ke Daftar Sewa
            </Link>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-gray-900">Detail Sewa: {{ tenancy.tenant?.name }}</h2>
                <StatusBadge :status="tenancy.status" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Penyewa -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Penyewa</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-2 gap-4">
                        <div><p class="text-sm text-gray-500">Nama</p><p class="font-medium">{{ tenancy.tenant?.name }}</p></div>
                        <div><p class="text-sm text-gray-500">Email</p><p class="font-medium">{{ tenancy.tenant?.email }}</p></div>
                        <div><p class="text-sm text-gray-500">No. WhatsApp</p><p class="font-medium">{{ tenancy.tenant?.whatsapp_number || '-' }}</p></div>
                    </CardContent>
                </Card>

                <!-- Info Sewa -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Kamar & Sewa</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-2 gap-4">
                        <div><p class="text-sm text-gray-500">Kamar</p><p class="font-medium">{{ tenancy.room?.name }} (No. {{ tenancy.room?.room_number }})</p></div>
                        <div><p class="text-sm text-gray-500">Kapasitas Dihuni</p><p class="font-medium">{{ tenancy.occupant_count }} Orang</p></div>
                        <div><p class="text-sm text-gray-500">Mulai Sewa</p><p class="font-medium">{{ formatDate(tenancy.start_date) }}</p></div>
                        <div><p class="text-sm text-gray-500">Kos</p><p class="font-medium">{{ tenancy.room?.boarding_house?.name }}</p></div>
                    </CardContent>
                </Card>
            </div>

            <!-- Konfirmasi Pembayaran -->
            <div class="space-y-6">
                <Card v-if="pendingPayment" class="border-orange-200 shadow-md">
                    <CardHeader class="bg-orange-50 border-b border-orange-100">
                        <CardTitle class="text-orange-900">Perlu Konfirmasi Pembayaran</CardTitle>
                    </CardHeader>
                    <CardContent class="pt-6 space-y-4">
                        <div class="flex justify-between border-b pb-4">
                            <div>
                                <p class="text-sm text-gray-500">Nominal</p>
                                <p class="font-bold text-lg">{{ formatPrice(pendingPayment.amount) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Tanggal Upload</p>
                                <p class="font-medium text-sm">{{ formatDate(pendingPayment.payment_date) }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500 mb-2">Bukti Transfer:</p>
                            <a :href="pendingPayment.proof_file_path" target="_blank">
                                <img :src="pendingPayment.proof_file_path" class="w-full h-48 object-cover rounded border hover:opacity-90 cursor-pointer" />
                            </a>
                            <p class="text-xs text-gray-500 mt-1">Klik gambar untuk memperbesar</p>
                        </div>

                        <div v-if="isRejecting" class="pt-2">
                            <Label for="note">Catatan Penolakan</Label>
                            <Textarea id="note" v-model="form.review_note" placeholder="Misal: Bukti transfer tidak jelas" rows="2" class="mt-1" />
                            <div class="flex justify-end gap-2 mt-2">
                                <Button variant="ghost" size="sm" @click="isRejecting = false">Batal</Button>
                                <Button variant="destructive" size="sm" :disabled="form.processing" @click="confirmAction('reject')">Konfirmasi Tolak</Button>
                            </div>
                        </div>
                        <div v-else class="flex gap-2 pt-2">
                            <Button variant="outline" class="flex-1 border-red-200 text-red-600 hover:bg-red-50" @click="confirmAction('reject')">Tolak</Button>
                            <Button class="flex-1 bg-green-600 hover:bg-green-700" :disabled="form.processing" @click="confirmAction('approve')">Terima Pembayaran</Button>
                        </div>
                    </CardContent>
                </Card>

                <Card v-else-if="activeInvoice">
                    <CardHeader>
                        <CardTitle>Status Tagihan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-center py-4">
                            <StatusBadge :status="activeInvoice.status" class="text-lg px-4 py-2 mb-2 inline-flex" />
                            <p class="text-gray-500 text-sm mt-2">Total: {{ formatPrice(activeInvoice.amount) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
