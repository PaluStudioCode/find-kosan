<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Deferred } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Clock, CheckCircle, Banknote } from 'lucide-vue-next';
import StatusBadge from '@/components/StatusBadge.vue';

defineProps({ withdrawals: Object, metrics: Object });

const formatRupiah = (amount) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(amount || 0);

const formatRupiahCompact = (amount) => {
    return new Intl.NumberFormat('id-ID', { 
        style: 'currency', 
        currency: 'IDR',
        notation: 'compact',
        maximumFractionDigits: 1
    }).format(amount || 0);
};
</script>

<template>
    <AppLayout>
        <Head title="Penarikan Pemilik" />
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Penarikan Pemilik</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-1">Tinjau, setujui, lalu selesaikan transfer manual kepada pemilik kos.</p>
        </div>

        <!-- WITHDRAWAL METRICS -->
        <Deferred :data="['metrics']">
            <template #fallback>
                <!-- Metrics Skeleton -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <Card v-for="n in 3" :key="'metric-skel-'+n" class="border shadow-sm bg-white dark:bg-slate-900">
                        <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                            <div class="h-4 w-28 bg-slate-200 dark:bg-slate-800 rounded animate-pulse"></div>
                            <div class="w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-800 animate-pulse"></div>
                        </CardHeader>
                        <CardContent class="border-0">
                            <div class="h-8 w-32 bg-slate-200 dark:bg-slate-800 rounded animate-pulse"></div>
                        </CardContent>
                    </Card>
                </div>
            </template>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Pending -->
                <Card class="border border-orange-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-orange-50/50 to-white dark:bg-slate-900 dark:bg-none">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                        <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Menunggu Pencairan</CardTitle>
                        <div class="w-8 h-8 rounded-xl bg-orange-100 dark:bg-orange-500/20 flex items-center justify-center">
                            <Clock class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                        </div>
                    </CardHeader>
                    <CardContent class="border-0">
                        <div class="text-3xl font-extrabold text-slate-800 dark:text-white truncate" :title="formatRupiah(metrics.pendingAmount)">{{ formatRupiahCompact(metrics.pendingAmount) }}</div>
                    </CardContent>
                </Card>

                <!-- Completed -->
                <Card class="border border-emerald-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-emerald-50/50 to-white dark:bg-slate-900 dark:bg-none">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                        <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Pencairan Berhasil</CardTitle>
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center">
                            <CheckCircle class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </CardHeader>
                    <CardContent class="border-0">
                        <div class="text-3xl font-extrabold text-slate-800 dark:text-white truncate" :title="formatRupiah(metrics.completedPayouts)">{{ formatRupiahCompact(metrics.completedPayouts) }}</div>
                    </CardContent>
                </Card>

                <!-- Collected PPH -->
                <Card class="border border-purple-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-purple-50/50 to-white dark:bg-slate-900 dark:bg-none">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                        <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Total PPh Terkumpul</CardTitle>
                        <div class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center">
                            <Banknote class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                        </div>
                    </CardHeader>
                    <CardContent class="border-0">
                        <div class="text-3xl font-extrabold text-slate-800 dark:text-white truncate" :title="formatRupiah(metrics.collectedPph)">{{ formatRupiahCompact(metrics.collectedPph) }}</div>
                    </CardContent>
                </Card>
            </div>
        </Deferred>

        <Card class="border-0 dark:bg-slate-900 shadow-sm dark:border-slate-800">
            <CardHeader class="border-b dark:border-slate-800"><CardTitle class="dark:text-white">Daftar Permintaan Penarikan</CardTitle></CardHeader>
            <CardContent class="p-0 border-0">
                <Deferred :data="['withdrawals']">
                    <template #fallback>
                        <div class="animate-pulse divide-y dark:divide-slate-800">
                            <div v-for="n in 10" :key="n" class="p-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="space-y-2">
                                    <div class="h-6 w-32 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                    <div class="h-5 w-48 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                    <div class="h-4 w-64 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="h-6 w-24 bg-slate-200 dark:bg-slate-800 rounded-full"></div>
                                    <div class="h-9 w-20 bg-slate-200 dark:bg-slate-800 rounded-md"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                <div v-if="withdrawals.data.length" class="divide-y dark:divide-slate-800">
                    <div v-for="withdrawal in withdrawals.data" :key="withdrawal.id" class="p-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between hover:bg-gray-50/50 dark:hover:bg-slate-800/50 transition-colors">
                        <div>
                            <p class="font-bold text-lg dark:text-slate-200">{{ formatRupiah(withdrawal.amount) }}</p>
                            <p class="font-medium dark:text-slate-300">{{ withdrawal.admin?.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-slate-400">{{ withdrawal.bank_name }} - {{ withdrawal.account_number }} a.n. {{ withdrawal.account_holder_name }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <StatusBadge :status="withdrawal.status" />
                            <Link :href="route('superadmin.withdrawals.show', withdrawal.id)"><Button variant="outline" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Tinjau</Button></Link>
                        </div>
                    </div>
                </div>
                <p v-else class="p-10 text-center text-gray-500 dark:text-slate-500">Belum ada permintaan penarikan.</p>
                </Deferred>

                <div class="p-4 bg-gray-50 dark:bg-slate-900 rounded-b-lg border-t dark:border-slate-800">
                    <Pagination :links="withdrawals ? withdrawals.links : []" />
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
