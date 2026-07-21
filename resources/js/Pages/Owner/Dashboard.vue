<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Building2, BedDouble, Key, Clock } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Button } from '@/components/ui/button';

const props = defineProps({
    metrics: Object,
    recentPayments: Array,
    activityLogs: Array,
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
};
</script>

<template>
    <AppLayout>
        <Head title="Dashboard Pemilik Kos" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Dashboard Pemilik Kos</h2>
            <p class="text-gray-500 mt-1">Ringkasan properti dan transaksi Anda.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Total Properti Kos</CardTitle>
                    <Building2 class="w-4 h-4 text-primary" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ metrics.totalKos }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Total Kamar</CardTitle>
                    <BedDouble class="w-4 h-4 text-gray-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ metrics.totalRooms }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Kamar Terisi</CardTitle>
                    <Key class="w-4 h-4 text-green-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">{{ metrics.occupiedRooms }}</div>
                    <p class="text-xs text-gray-500 mt-1">{{ metrics.availableRooms }} kamar tersedia</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Menunggu Konfirmasi</CardTitle>
                    <Clock class="w-4 h-4 text-orange-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ metrics.pendingPayments }}</div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            <Card class="lg:col-span-2 flex flex-col h-fit max-h-[450px]">
                <CardHeader>
                    <CardTitle>Pembayaran Terbaru</CardTitle>
                </CardHeader>
                <CardContent class="overflow-y-auto pr-4">
                    <div v-if="recentPayments.length > 0" class="space-y-4">
                        <div v-for="payment in recentPayments" :key="payment.id" class="flex items-center justify-between p-4 border rounded-lg">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center font-bold text-gray-500">
                                    {{ payment.invoice?.tenant?.name?.charAt(0) || 'U' }}
                                </div>
                                <div>
                                    <p class="font-medium">{{ payment.invoice?.tenant?.name }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ payment.invoice?.tenancy?.room?.boardingHouse?.name }} - {{ payment.invoice?.tenancy?.room?.name }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold">{{ formatRupiah(payment.amount) }}</p>
                                <StatusBadge :status="payment.status" class="mt-1" />
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        Belum ada riwayat pembayaran.
                    </div>
                </CardContent>
            </Card>

            <Card class="flex flex-col h-fit max-h-[450px]">
                <CardHeader>
                    <CardTitle>Aktivitas Terakhir</CardTitle>
                </CardHeader>
                <CardContent class="overflow-y-auto pr-4 pt-1">
                    <div v-if="activityLogs.length > 0" class="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gray-100">
                        <div v-for="log in activityLogs" :key="log.id" class="relative flex items-start gap-4">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-indigo-50 text-indigo-500 shadow-sm shrink-0 z-10">
                                <Clock class="w-4 h-4" />
                            </div>
                            <div class="pt-1 w-full">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <h4 class="font-semibold text-sm text-gray-900 capitalize">{{ log.action.replace('.', ' ') }}</h4>
                                    <time class="text-xs text-gray-500 whitespace-nowrap">{{ new Date(log.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) }}</time>
                                </div>
                                <p class="text-sm text-gray-600">{{ log.description }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        Belum ada aktivitas.
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
