<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Deferred } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch, nextTick } from 'vue';
import { useDark } from '@vueuse/core';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Users, FileCheck, Flag, Wallet, AlertCircle, Building2, Banknote, TrendingUp, Landmark, Receipt } from 'lucide-vue-next';
import StatusBadge from '@/components/StatusBadge.vue';

import { Line, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, LineElement, PointElement, ArcElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, ArcElement, CategoryScale, LinearScale);

const props = defineProps({
    metrics: Object,
    financials: Object,
    charts: Object,
    recentVerifications: Array,
    recentReports: Array,
    recentWithdrawals: Array,
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
};

const formatRupiahCompact = (amount) => {
    return new Intl.NumberFormat('id-ID', { 
        style: 'currency', 
        currency: 'IDR',
        notation: 'compact',
        maximumFractionDigits: 1
    }).format(amount);
};

const isDark = useDark();
const showCharts = ref(true);

watch(isDark, async () => {
    showCharts.value = false;
    await nextTick();
    // Beri sedikit jeda agar DOM benar-benar dibersihkan sebelum dirender ulang
    setTimeout(() => {
        showCharts.value = true;
    }, 50);
});

const growthChartData = computed(() => ({
    labels: props.charts?.growthLabels || [],
    datasets: [
        {
            label: 'Penyewa',
            borderColor: isDark.value ? '#60a5fa' : '#3b82f6',
            backgroundColor: isDark.value ? '#60a5fa' : '#3b82f6',
            data: props.charts?.growthTenantsData || [],
            tension: 0.4
        },
        {
            label: 'Pemilik Kos',
            borderColor: isDark.value ? '#34d399' : '#10b981',
            backgroundColor: isDark.value ? '#34d399' : '#10b981',
            data: props.charts?.growthOwnersData || [],
            tension: 0.4
        }
    ]
}));

const growthChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1200,
        easing: 'easeOutQuart'
    },
    plugins: {
        legend: { position: 'bottom', labels: { color: isDark.value ? '#cbd5e1' : '#64748b' } }
    },
    scales: {
        x: { ticks: { color: isDark.value ? '#94a3b8' : '#64748b' }, grid: { color: isDark.value ? '#334155' : '#f1f5f9' } },
        y: { beginAtZero: true, ticks: { stepSize: 1, color: isDark.value ? '#94a3b8' : '#64748b' }, grid: { color: isDark.value ? '#334155' : '#f1f5f9' } }
    }
}));

const propertyChartData = computed(() => ({
    labels: props.charts?.propertyStatusLabels || [],
    datasets: [
        {
            backgroundColor: isDark.value 
                ? ['#34d399', '#fbbf24', '#475569', '#f87171']
                : ['#10b981', '#f59e0b', '#cbd5e1', '#ef4444'],
            borderColor: isDark.value ? '#1e293b' : '#ffffff',
            borderWidth: 2,
            data: props.charts?.propertyStatusData || []
        }
    ]
}));

const propertyChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1000,
        easing: 'easeOutQuart'
    },
    plugins: {
        legend: { position: 'right', labels: { color: isDark.value ? '#cbd5e1' : '#64748b' } }
    }
}));
</script>

<template>
    <AppLayout>
        <Head title="Dashboard Super Admin" />

        <Deferred :data="['metrics', 'financials', 'charts', 'recentVerifications', 'recentReports', 'recentWithdrawals']">
            <template #fallback>
                <div class="animate-pulse">
                    <!-- Header Ringkas -->
                    <div class="mb-6">
                        <div class="h-8 w-64 bg-slate-200 dark:bg-slate-800 rounded mb-2"></div>
                        <div class="h-4 w-48 bg-slate-200 dark:bg-slate-800 rounded"></div>
                    </div>
                    
                    <!-- Financials Skeleton -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <Card v-for="n in 4" :key="'fin-skel-'+n" class="border-0 shadow-sm bg-white dark:bg-slate-900">
                            <CardContent class="border-0 p-6 flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-slate-200 dark:bg-slate-800 shrink-0"></div>
                                <div>
                                    <div class="h-4 w-24 bg-slate-200 dark:bg-slate-800 rounded mb-2"></div>
                                    <div class="h-6 w-32 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Metrics Cards Skeleton -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <Card v-for="n in 4" :key="'metric-'+n" class="border-0 shadow-sm bg-white dark:bg-slate-900">
                            <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                                <div class="h-4 w-24 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                <div class="w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-800"></div>
                            </CardHeader>
                            <CardContent class="border-0">
                                <div class="h-8 w-16 bg-slate-200 dark:bg-slate-800 rounded mb-2"></div>
                                <div class="h-3 w-32 bg-slate-200 dark:bg-slate-800 rounded mt-2"></div>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <!-- Charts Skeleton -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                        <Card class="lg:col-span-2 border-0 shadow-sm dark:bg-slate-900">
                            <CardHeader class="border-0">
                                <div class="h-5 w-48 bg-slate-200 dark:bg-slate-800 rounded mb-2"></div>
                                <div class="h-4 w-64 bg-slate-200 dark:bg-slate-800 rounded"></div>
                            </CardHeader>
                            <CardContent class="border-0 pt-6">
                                <div class="h-[250px] w-full bg-slate-100 dark:bg-slate-800/50 rounded-lg"></div>
                            </CardContent>
                        </Card>
                        <Card class="border-0 shadow-sm dark:bg-slate-900">
                            <CardHeader class="border-0">
                                <div class="h-5 w-40 bg-slate-200 dark:bg-slate-800 rounded mb-2"></div>
                                <div class="h-4 w-48 bg-slate-200 dark:bg-slate-800 rounded"></div>
                            </CardHeader>
                            <CardContent class="border-0 flex items-center justify-center h-[280px]">
                                <div class="h-[200px] w-[200px] rounded-full bg-slate-100 dark:bg-slate-800/50"></div>
                            </CardContent>
                        </Card>
                    </div>
                    
                    <!-- Action Panels Skeleton -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <Card v-for="n in 3" :key="'panel-'+n" class="border-0 shadow-sm dark:bg-slate-900">
                            <CardHeader class="border-b border-slate-100 dark:border-slate-800 pb-3 flex flex-row items-center justify-between">
                                <div class="h-5 w-32 bg-slate-200 dark:bg-slate-800 rounded"></div>
                                <div class="h-4 w-16 bg-slate-200 dark:bg-slate-800 rounded"></div>
                            </CardHeader>
                            <CardContent class="pt-4 border-0 space-y-3">
                                <div v-for="j in 3" :key="'item-'+n+'-'+j" class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl space-y-2">
                                    <div class="flex justify-between">
                                        <div class="h-4 w-24 bg-slate-200 dark:bg-slate-700 rounded"></div>
                                        <div class="h-4 w-16 bg-slate-200 dark:bg-slate-700 rounded"></div>
                                    </div>
                                    <div class="h-3 w-40 bg-slate-200 dark:bg-slate-700 rounded"></div>
                                    <div class="h-8 w-full bg-slate-200 dark:bg-slate-700 rounded-md mt-2"></div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </template>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard Super Admin</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-1">Ringkasan sistem secara keseluruhan.</p>
        </div>

        <!-- FINANCIAL METRICS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total GTV -->
            <Card class="border border-blue-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-blue-50/50 to-white dark:bg-slate-900 dark:bg-none">
                <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                    <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Total Uang Masuk</CardTitle>
                    <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center">
                        <TrendingUp class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                    </div>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="text-3xl font-extrabold text-slate-800 dark:text-white truncate" :title="formatRupiah(financials.totalGTV)">{{ formatRupiahCompact(financials.totalGTV) }}</div>
                </CardContent>
            </Card>

            <!-- Total Escrow -->
            <Card class="border border-emerald-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-emerald-50/50 to-white dark:bg-slate-900 dark:bg-none">
                <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                    <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Saldo Pemilik Kos</CardTitle>
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center">
                        <Landmark class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="text-3xl font-extrabold text-slate-800 dark:text-white truncate" :title="formatRupiah(financials.totalEscrow)">{{ formatRupiahCompact(financials.totalEscrow) }}</div>
                </CardContent>
            </Card>

            <!-- Total PPN -->
            <Card class="border border-purple-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-purple-50/50 to-white dark:bg-slate-900 dark:bg-none">
                <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                    <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Total PPN Terkumpul</CardTitle>
                    <div class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center">
                        <Receipt class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                    </div>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="text-3xl font-extrabold text-slate-800 dark:text-white truncate" :title="formatRupiah(financials.totalPpn)">{{ formatRupiahCompact(financials.totalPpn) }}</div>
                </CardContent>
            </Card>

            <!-- Total PPh -->
            <Card class="border border-orange-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-orange-50/50 to-white dark:bg-slate-900 dark:bg-none">
                <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                    <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Total PPh Terkumpul</CardTitle>
                    <div class="w-8 h-8 rounded-xl bg-orange-100 dark:bg-orange-500/20 flex items-center justify-center">
                        <Banknote class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                    </div>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="text-3xl font-extrabold text-slate-800 dark:text-white truncate" :title="formatRupiah(financials.totalPph)">{{ formatRupiahCompact(financials.totalPph) }}</div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Pengguna -->
            <Card class="relative overflow-hidden border-0 dark:border-0 shadow-lg bg-gradient-to-br from-blue-600 to-indigo-700 text-white transform hover:-translate-y-1 transition-all duration-300">
                <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-white/10 blur-2xl"></div>
                <CardHeader class="flex flex-row items-center justify-between pb-2 relative z-10 border-0">
                    <CardTitle class="text-sm font-medium text-blue-50 border-0">Pertumbuhan Pengguna</CardTitle>
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                        <Users class="w-4 h-4 text-white" />
                    </div>
                </CardHeader>
                <CardContent class="relative z-10 border-0">
                    <div class="text-3xl font-extrabold tracking-tight">{{ metrics.totalUsers }}</div>
                </CardContent>
            </Card>

            <!-- Verifikasi Properti -->
            <Card class="border border-orange-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-orange-50/50 to-white dark:bg-slate-900 dark:bg-none">
                <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                    <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Verifikasi Properti</CardTitle>
                    <div class="w-8 h-8 rounded-xl bg-orange-100 dark:bg-orange-500/20 flex items-center justify-center">
                        <FileCheck class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                    </div>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="text-3xl font-extrabold text-slate-800 dark:text-white">{{ metrics.pendingKosVerifications }}</div>
                </CardContent>
            </Card>

            <!-- Penarikan Dana -->
            <Card class="border border-emerald-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-emerald-50/50 to-white dark:bg-slate-900 dark:bg-none">
                <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                    <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Penarikan Dana</CardTitle>
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center">
                        <Wallet class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="text-3xl font-extrabold text-slate-800 dark:text-white">{{ metrics.pendingWithdrawals }}</div>
                </CardContent>
            </Card>

            <!-- Aduan Tertunda -->
            <Card class="border border-red-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-red-50/50 to-white dark:bg-slate-900 dark:bg-none">
                <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                    <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Laporan / Aduan</CardTitle>
                    <div class="w-8 h-8 rounded-xl bg-red-100 dark:bg-red-500/20 flex items-center justify-center">
                        <Flag class="w-4 h-4 text-red-600 dark:text-red-400" />
                    </div>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="text-3xl font-extrabold text-slate-800 dark:text-white">{{ metrics.pendingReports }}</div>
                </CardContent>
            </Card>
        </div>

        <!-- CHARTS SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <Card class="shadow-sm border border-slate-100 dark:border-0 lg:col-span-2 dark:bg-slate-900">
                <CardHeader class="border-0">
                    <CardTitle class="text-lg font-bold text-slate-800 dark:text-white border-0">Grafik Pertumbuhan Pengguna (6 Bulan Terakhir)</CardTitle>
                    <CardDescription class="dark:text-slate-400">Perbandingan pendaftaran akun Penyewa dan Pemilik Kos.</CardDescription>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="h-[300px] w-full">
                        <Line v-if="showCharts" :data="growthChartData" :options="growthChartOptions" />
                    </div>
                </CardContent>
            </Card>

            <Card class="shadow-sm border border-slate-100 dark:border-0 h-fit dark:bg-slate-900">
                <CardHeader class="border-0">
                    <CardTitle class="text-lg font-bold text-slate-800 dark:text-white border-0">Sebaran Status Properti</CardTitle>
                    <CardDescription class="dark:text-slate-400">Peta proporsi kos di sistem.</CardDescription>
                </CardHeader>
                <CardContent class="flex items-center justify-center border-0">
                    <div class="h-[250px] w-full max-w-[250px]">
                        <Doughnut v-if="showCharts" :data="propertyChartData" :options="propertyChartOptions" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ACTION PANELS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Antrean Penarikan Dana -->
            <Card class="relative overflow-hidden border-0 dark:border-0 shadow-sm bg-gradient-to-br from-white to-emerald-50/50 dark:bg-slate-900 dark:bg-none hover:shadow-md transition-shadow">
                <div class="absolute right-0 top-0 w-32 h-32 bg-emerald-100/50 dark:bg-emerald-500/10 rounded-full blur-3xl -z-10"></div>
                <CardHeader class="pb-3 border-b border-emerald-100/50 dark:border-slate-800 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-base font-bold flex items-center gap-2 text-slate-800 dark:text-white">
                            <Banknote class="w-4 h-4 text-emerald-600 dark:text-emerald-400" /> Penarikan Dana
                        </CardTitle>
                    </div>
                    <Link :href="route('superadmin.withdrawals.index')" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">Lihat Semua</Link>
                </CardHeader>
                <CardContent class="pt-4 border-0">
                    <div v-if="recentWithdrawals.length > 0" class="space-y-3">
                        <div v-for="wd in recentWithdrawals" :key="wd.id" class="p-3 bg-white dark:bg-slate-800/50 border border-emerald-100 dark:border-slate-700/50 shadow-sm rounded-xl hover:border-emerald-300 dark:hover:border-emerald-500/50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <div class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ wd.admin?.name }}</div>
                                <div class="font-bold text-sm text-emerald-600 dark:text-emerald-400">{{ formatRupiah(wd.amount) }}</div>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-1">
                                <Building2 class="w-3 h-3" /> {{ wd.bank_name }} - {{ wd.account_number }}
                            </div>
                            <Link :href="route('superadmin.withdrawals.show', wd.id)" class="block w-full text-center text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 hover:bg-emerald-600 dark:hover:bg-emerald-500 hover:text-white px-3 py-1.5 rounded-md transition-colors">
                                Proses Sekarang
                            </Link>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-slate-400 dark:text-slate-500 text-sm">
                        Tidak ada antrean penarikan dana.
                    </div>
                </CardContent>
            </Card>

            <!-- Antrean Verifikasi Kos -->
            <Card class="relative overflow-hidden border-0 dark:border-0 shadow-sm bg-gradient-to-br from-white to-orange-50/50 dark:bg-slate-900 dark:bg-none hover:shadow-md transition-shadow">
                <div class="absolute right-0 top-0 w-32 h-32 bg-orange-100/50 dark:bg-orange-500/10 rounded-full blur-3xl -z-10"></div>
                <CardHeader class="pb-3 border-b border-orange-100/50 dark:border-slate-800 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-base font-bold flex items-center gap-2 text-slate-800 dark:text-white">
                            <FileCheck class="w-4 h-4 text-orange-600 dark:text-orange-400" /> Verifikasi Kos
                        </CardTitle>
                    </div>
                    <Link :href="route('superadmin.verifications.index')" class="text-xs font-semibold text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300">Lihat Semua</Link>
                </CardHeader>
                <CardContent class="pt-4 border-0">
                    <div v-if="recentVerifications.length > 0" class="space-y-3">
                        <div v-for="kos in recentVerifications" :key="kos.id" class="p-3 bg-white dark:bg-slate-800/50 border border-orange-100 dark:border-slate-700/50 shadow-sm rounded-xl hover:border-orange-300 dark:hover:border-orange-500/50 transition-colors">
                            <div class="font-bold text-sm text-slate-800 dark:text-slate-200 mb-1 truncate">{{ kos.name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 mb-3">Pemilik: {{ kos.admin?.name }}</div>
                            <Link :href="route('superadmin.verifications.show', kos.id)" class="block w-full text-center text-xs font-semibold text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-500/10 hover:bg-orange-600 dark:hover:bg-orange-500 hover:text-white px-3 py-1.5 rounded-md transition-colors">
                                Tinjau Dokumen
                            </Link>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-slate-400 dark:text-slate-500 text-sm">
                        Tidak ada antrean verifikasi saat ini.
                    </div>
                </CardContent>
            </Card>

            <!-- Laporan Terbaru -->
            <Card class="relative overflow-hidden border-0 dark:border-0 shadow-sm bg-gradient-to-br from-white to-red-50/50 dark:bg-slate-900 dark:bg-none hover:shadow-md transition-shadow">
                <div class="absolute right-0 top-0 w-32 h-32 bg-red-100/50 dark:bg-red-500/10 rounded-full blur-3xl -z-10"></div>
                <CardHeader class="pb-3 border-b border-red-100/50 dark:border-slate-800 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-base font-bold flex items-center gap-2 text-slate-800 dark:text-white">
                            <AlertCircle class="w-4 h-4 text-red-600 dark:text-red-400" /> Laporan Terbaru
                        </CardTitle>
                    </div>
                    <Link :href="route('superadmin.reports.index')" class="text-xs font-semibold text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">Lihat Semua</Link>
                </CardHeader>
                <CardContent class="pt-4 border-0">
                    <div v-if="recentReports.length > 0" class="space-y-3">
                        <div v-for="report in recentReports" :key="report.id" class="p-3 bg-white dark:bg-slate-800/50 border border-red-100 dark:border-slate-700/50 shadow-sm rounded-xl hover:border-red-300 dark:hover:border-red-500/50 transition-colors">
                            <div class="font-bold text-sm text-slate-800 dark:text-slate-200 mb-1 line-clamp-1">{{ report.title }}</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 mb-2 italic line-clamp-2">"{{ report.description }}"</div>
                            <Link :href="route('superadmin.reports.show', report.id)" class="block w-full text-center text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-500/10 hover:bg-red-600 dark:hover:bg-red-500 hover:text-white px-3 py-1.5 rounded-md transition-colors">
                                Investigasi
                            </Link>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-slate-400 dark:text-slate-500 text-sm">
                        Tidak ada laporan yang perlu ditangani.
                    </div>
                </CardContent>
            </Card>

        </div>
        </Deferred>
    </AppLayout>
</template>
