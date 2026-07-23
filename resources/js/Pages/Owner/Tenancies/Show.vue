<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import StatusBadge from '@/Components/StatusBadge.vue';
import { ChevronLeft, CheckCircle, XCircle } from 'lucide-vue-next';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

const props = defineProps({
    tenancy: Object
});

const formatDate = (date) => {
    if (!date) return '-';
    return format(new Date(date), 'dd MMM yyyy', { locale: id });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
};

const activeInvoice = computed(() => props.tenancy.invoices[0]);

const showEndTenancyDialog = ref(false);

const submitEndTenancy = () => {
    useForm({}).post(route('owner.tenancies.end', props.tenancy.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEndTenancyDialog.value = false;
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

            <div class="space-y-6">
                <Card v-if="tenancy.status === 'selesai'" class="bg-gradient-to-br from-gray-50 to-slate-100 border-gray-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gray-400"></div>
                    <CardContent class="py-10">
                        <div class="text-center space-y-3">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto shadow-sm mb-2 border border-gray-100">
                                <CheckCircle class="w-8 h-8 text-gray-500" />
                            </div>
                            <h3 class="text-xl font-extrabold text-gray-900">Sewa Telah Selesai</h3>
                            <p class="text-gray-600 text-sm max-w-sm mx-auto">Kontrak sewa dengan penyewa ini telah berakhir. Tidak ada tagihan baru yang akan dibuat.</p>
                        </div>
                    </CardContent>
                </Card>

                <Card v-else-if="activeInvoice && activeInvoice.status === 'lunas'" class="bg-gradient-to-br from-emerald-50 to-teal-50 border-emerald-200 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-emerald-500"></div>
                    <CardContent class="py-10">
                        <div class="text-center space-y-3">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto shadow-sm mb-2">
                                <CheckCircle class="w-8 h-8 text-emerald-500" />
                            </div>
                            <h3 class="text-xl font-extrabold text-emerald-900">Pembayaran Terverifikasi</h3>
                            <p class="text-emerald-700 text-sm max-w-sm mx-auto">Tagihan periode ini sudah lunas. Penyewa aktif dan tidak memiliki tanggungan saat ini.</p>
                        </div>
                    </CardContent>
                </Card>

                <Card v-else-if="activeInvoice" class="border-gray-200 shadow-sm">
                    <CardHeader class="border-b bg-gray-50/50 pb-4">
                        <CardTitle class="text-gray-900 text-base">Status Tagihan Berjalan</CardTitle>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div class="text-center">
                            <p class="text-sm text-gray-500 mb-1">Total Tagihan</p>
                            <p class="font-extrabold text-3xl text-gray-900 mb-4">{{ formatPrice(activeInvoice.amount) }}</p>
                            <StatusBadge :status="activeInvoice.status" class="px-3 py-1 mb-2 inline-flex" />
                            <p class="text-gray-500 text-xs mt-3">Menunggu penyewa melakukan pembayaran dan mengunggah bukti.</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Manajemen Sewa -->
                <Card v-if="tenancy.status === 'aktif'" class="border-red-200 shadow-sm mt-6">
                    <CardContent class="pt-6">
                        <p class="text-sm text-gray-600 mb-4 leading-relaxed">
                            Akhiri masa sewa jika penyewa telah keluar atau kontrak berakhir. Tindakan ini akan menghentikan sistem tagihan otomatis dan mengembalikan status ketersediaan kamar.
                        </p>
                        <Button variant="destructive" class="w-full font-bold" @click="showEndTenancyDialog = true">Akhiri Masa Sewa</Button>
                    </CardContent>
                </Card>
            </div>
        </div>



        <!-- Confirm End Tenancy Dialog -->
        <Dialog :open="showEndTenancyDialog" @update:open="showEndTenancyDialog = $event">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Konfirmasi Akhiri Sewa</DialogTitle>
                    <DialogDescription class="pt-3">
                        Apakah Anda yakin ingin mengakhiri masa sewa ini? Tagihan otomatis akan dihentikan dan status kamar akan diperbarui. Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="mt-4 flex gap-2 sm:justify-end">
                    <Button variant="outline" @click="showEndTenancyDialog = false">Batal</Button>
                    <Button variant="destructive" @click="submitEndTenancy">Ya, Akhiri Sewa</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>



    </AppLayout>
</template>
