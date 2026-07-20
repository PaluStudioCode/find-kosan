<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FileText, AlertCircle, Clock, Search } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Button } from '@/components/ui/button';

const props = defineProps({
    metrics: Object,
    recentInvoices: Array,
    recentPayments: Array,
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
};
</script>

<template>
    <PublicLayout>
        <Head title="Dashboard Penyewa" />
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Dashboard Penyewa</h2>
            <p class="text-gray-500 mt-1">Selamat datang kembali, {{ $page.props.auth.user.name }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Belum Dibayar</CardTitle>
                    <FileText class="w-4 h-4 text-gray-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ metrics.unpaidInvoices }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Jatuh Tempo</CardTitle>
                    <AlertCircle class="w-4 h-4 text-red-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-600">{{ metrics.overdueInvoices }}</div>
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <Card>
                <CardHeader>
                    <CardTitle>Tagihan Terbaru</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="recentInvoices.length > 0" class="space-y-4">
                        <div v-for="invoice in recentInvoices" :key="invoice.id" class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <p class="font-medium">Tagihan {{ invoice.tenancy?.room?.name }}</p>
                                <p class="text-sm text-gray-500">{{ new Date(invoice.period_start).toLocaleDateString('id-ID') }} - {{ new Date(invoice.period_end).toLocaleDateString('id-ID') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold">{{ formatRupiah(invoice.amount) }}</p>
                                <StatusBadge :status="invoice.status" class="mt-1" />
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        Belum ada tagihan.
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Pembayaran Terbaru</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="recentPayments.length > 0" class="space-y-4">
                        <div v-for="payment in recentPayments" :key="payment.id" class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <p class="font-medium">Pembayaran {{ payment.invoice?.tenancy?.room?.name }}</p>
                                <p class="text-sm text-gray-500">{{ new Date(payment.payment_date || payment.created_at).toLocaleDateString('id-ID') }}</p>
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
        </div>
        </div>
    </PublicLayout>
</template>
