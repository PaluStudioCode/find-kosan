<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref, watch, nextTick } from 'vue';
import { useDark } from '@vueuse/core';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Building2, BedDouble, Key, Clock, Wallet, TrendingUp, AlertCircle, Coins, CheckCircle2, CalendarClock, MessageSquare, Star, ArrowRight, MapPin } from 'lucide-vue-next';
import StatusBadge from '@/components/StatusBadge.vue';

import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    metrics: Object,
    recentTransactions: Array,
    activityLogs: Array,
    upcomingDueInvoices: Array,
    vacantRooms: Array,
    recentReviews: Array,
    charts: Object,
});

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(amount);
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
    setTimeout(() => {
        showCharts.value = true;
    }, 50);
});

const revenueChartData = computed(() => ({
    labels: props.charts.revenueLabels,
    datasets: [{
        label: 'Pendapatan',
        backgroundColor: isDark.value ? '#34d399' : '#10b981',
        borderRadius: 4,
        data: props.charts.revenueData
    }]
}));

const revenueChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1200,
        easing: 'easeOutQuart'
    },
    plugins: {
        legend: { display: false }
    },
    scales: {
        x: { ticks: { color: isDark.value ? '#94a3b8' : '#64748b' }, grid: { color: isDark.value ? '#334155' : '#f1f5f9' } },
        y: { beginAtZero: true, ticks: { color: isDark.value ? '#94a3b8' : '#64748b' }, grid: { color: isDark.value ? '#334155' : '#f1f5f9' } }
    }
}));

const capacityChartData = computed(() => ({
    labels: props.charts.propertiesCapacity.map(p => p.name),
    datasets: [
        {
            label: 'Terisi',
            backgroundColor: isDark.value ? '#60a5fa' : '#3b82f6',
            data: props.charts.propertiesCapacity.map(p => p.occupied_rooms)
        },
        {
            label: 'Kosong',
            backgroundColor: isDark.value ? '#475569' : '#cbd5e1',
            data: props.charts.propertiesCapacity.map(p => p.vacant_rooms)
        }
    ]
}));

const capacityChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1200,
        easing: 'easeOutQuart'
    },
    plugins: {
        legend: { labels: { color: isDark.value ? '#cbd5e1' : '#64748b' } }
    },
    scales: {
        x: { stacked: true, ticks: { color: isDark.value ? '#94a3b8' : '#64748b' }, grid: { color: isDark.value ? '#334155' : '#f1f5f9' } },
        y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, color: isDark.value ? '#94a3b8' : '#64748b' }, grid: { color: isDark.value ? '#334155' : '#f1f5f9' } }
    }
}));
</script>

<template>
    <AppLayout>
        <Head title="Dashboard Pemilik Kos" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard Pemilik Kos</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-1">Ringkasan properti dan transaksi Anda.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Pendapatan Bulan Ini (Premium Green Gradient) -->
            <Card class="relative overflow-hidden border-0 shadow-lg bg-gradient-to-br from-emerald-500 to-teal-600 text-white transform hover:-translate-y-1 transition-all duration-300">
                <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-white/10 blur-2xl"></div>
                <CardHeader class="flex flex-row items-center justify-between pb-2 relative z-10 border-0">
                    <CardTitle class="text-sm font-medium text-emerald-50 border-0">Pendapatan Bulan Ini</CardTitle>
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                        <TrendingUp class="w-4 h-4 text-white" />
                    </div>
                </CardHeader>
                <CardContent class="relative z-10 border-0">
                    <div class="text-3xl font-extrabold tracking-tight truncate" :title="formatRupiah(metrics.currentMonthRevenue)">{{ formatRupiahCompact(metrics.currentMonthRevenue) }}</div>
                    <p class="text-xs text-emerald-100 mt-2 font-medium flex items-center">
                        <CheckCircle2 class="w-3.5 h-3.5 mr-1" v-if="metrics.currentMonthRevenue > 0" /> 
                        Total uang sewa lunas
                    </p>
                </CardContent>
            </Card>

            <!-- Saldo Dompet (Premium Dark Mode) -->
            <Card class="relative overflow-hidden border-0 shadow-lg bg-gradient-to-br from-slate-900 to-slate-800 text-white transform hover:-translate-y-1 transition-all duration-300">
                <div class="absolute -right-4 -bottom-4 w-32 h-32 rounded-full bg-indigo-500/10 blur-xl"></div>
                <CardHeader class="flex flex-row items-center justify-between pb-2 relative z-10 border-0">
                    <CardTitle class="text-sm font-medium text-slate-300 border-0">Saldo Dompet</CardTitle>
                    <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center">
                        <Wallet class="w-4 h-4 text-indigo-300" />
                    </div>
                </CardHeader>
                <CardContent class="relative z-10 border-0">
                    <div class="text-3xl font-extrabold tracking-tight truncate" :title="formatRupiah(metrics.walletBalance)">{{ formatRupiahCompact(metrics.walletBalance) }}</div>
                    <Link :href="route('admin.wallet.index')" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-300 hover:text-white mt-3 transition-colors bg-white/10 px-3 py-1.5 rounded-full">
                        Tarik Dana <ArrowRight class="w-3 h-3" />
                    </Link>
                </CardContent>
            </Card>

            <!-- Tingkat Keterisian (Sleek Light Card) -->
            <Card class="border border-slate-100 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-white dark:bg-slate-900">
                <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                    <CardTitle class="text-sm font-medium text-slate-500 dark:text-slate-400 border-0">Keterisian Kamar</CardTitle>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-500/20 flex items-center justify-center">
                        <Building2 class="w-4 h-4 text-blue-500 dark:text-blue-400" />
                    </div>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="text-2xl font-bold text-slate-800 dark:text-white">{{ metrics.occupancyRate }}% <span class="text-sm font-normal text-slate-400 dark:text-slate-500">Terisi</span></div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 mt-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-400 to-blue-600 dark:from-blue-500 dark:to-blue-700 h-2 rounded-full" :style="{ width: `${metrics.occupancyRate}%` }"></div>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-3 font-medium">
                        <span class="text-slate-800 dark:text-slate-200 font-bold">{{ metrics.occupiedRooms }}</span> dari {{ metrics.totalRooms }} total kamar
                    </p>
                </CardContent>
            </Card>

            <!-- Tagihan Jatuh Tempo (Soft Alert Card) -->
            <Card class="border border-orange-200 dark:border-0 shadow-sm hover:shadow-md transition-shadow bg-gradient-to-b from-orange-50/50 to-white dark:bg-slate-900 dark:bg-none">
                <CardHeader class="flex flex-row items-center justify-between pb-2 border-0">
                    <CardTitle class="text-sm font-medium text-orange-700 dark:text-orange-400 border-0">Tagihan Menunggak</CardTitle>
                    <div class="w-8 h-8 rounded-xl bg-orange-100 dark:bg-orange-500/20 flex items-center justify-center">
                        <AlertCircle class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                    </div>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="text-2xl font-bold text-orange-900 dark:text-white">{{ metrics.pendingInvoices }} <span class="text-sm font-normal text-orange-600/70 dark:text-orange-500/70">Tagihan</span></div>
                    <div class="mt-3 flex items-center gap-2 text-xs font-medium text-orange-600 dark:text-orange-400 bg-orange-100/50 dark:bg-orange-500/20 px-2.5 py-1.5 rounded-md w-fit">
                        <Clock class="w-3.5 h-3.5" /> Belum Lunas
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- CHARTS SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <Card class="shadow-sm border border-slate-100 dark:border-0 dark:bg-slate-900">
                <CardHeader class="border-0">
                    <CardTitle class="text-lg font-bold text-slate-800 dark:text-white border-0">Tren Pendapatan (6 Bulan Terakhir)</CardTitle>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="h-[300px] w-full">
                        <Bar v-if="showCharts" :data="revenueChartData" :options="revenueChartOptions" />
                    </div>
                </CardContent>
            </Card>

            <Card class="shadow-sm border border-slate-100 dark:border-0 dark:bg-slate-900">
                <CardHeader class="border-0">
                    <CardTitle class="text-lg font-bold text-slate-800 dark:text-white border-0">Status Kapasitas Properti</CardTitle>
                </CardHeader>
                <CardContent class="border-0">
                    <div class="h-[300px] w-full">
                        <Bar v-if="showCharts" :data="capacityChartData" :options="capacityChartOptions" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- KOLOM KIRI -->
            <div class="space-y-6">
                <!-- Pengingat Jatuh Tempo -->
                <Card class="relative overflow-hidden border-0 dark:border-0 shadow-sm bg-gradient-to-br from-white to-orange-50/50 dark:bg-slate-900 dark:bg-none hover:shadow-md transition-shadow">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-orange-100/50 dark:bg-orange-500/10 rounded-full blur-3xl -z-10"></div>
                    <CardHeader class="pb-3 border-b border-orange-100/50 dark:border-slate-800">
                        <CardTitle class="text-lg font-bold flex items-center gap-2 text-slate-800 dark:text-white">
                            <div class="p-2 bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400 rounded-lg">
                                <CalendarClock class="w-5 h-5" />
                            </div> 
                            Pengingat Jatuh Tempo
                        </CardTitle>
                        <CardDescription class="dark:text-slate-400">Penyewa yang tagihannya mendekati batas akhir pembayaran.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-4 border-0">
                        <div v-if="upcomingDueInvoices.length > 0" class="space-y-3">
                            <div v-for="invoice in upcomingDueInvoices" :key="invoice.id" class="flex items-center justify-between p-3 bg-white dark:bg-slate-800/50 border border-orange-100 dark:border-slate-700/50 shadow-sm rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-10 bg-orange-400 dark:bg-orange-500 rounded-full"></div>
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ invoice.user?.name }}</span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ invoice.tenancy?.room?.boardingHouse?.name }} - {{ invoice.tenancy?.room?.name }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="text-[10px] font-bold px-2 py-1 bg-orange-100 dark:bg-orange-500/20 text-orange-700 dark:text-orange-400 rounded-md mb-1">Jatuh Tempo: {{ new Date(invoice.due_date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) }}</span>
                                    <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ formatRupiah(invoice.amount) }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-6 text-slate-400 dark:text-slate-500 text-sm">
                            Tidak ada tagihan jatuh tempo dalam waktu dekat.
                        </div>
                    </CardContent>
                </Card>

                <!-- Transaksi Terakhir -->
                <Card class="shadow-sm border border-slate-100 dark:border-0 dark:bg-slate-900">
                    <CardHeader class="pb-3 flex flex-row items-center justify-between border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <CardTitle class="text-lg font-bold flex items-center gap-2 text-slate-800 dark:text-white">
                                <Coins class="w-5 h-5 text-emerald-500 dark:text-emerald-400" /> Transaksi Terakhir
                            </CardTitle>
                            <CardDescription class="dark:text-slate-400">Riwayat pembayaran sukses terbaru.</CardDescription>
                        </div>
                        <Link :href="route('admin.wallet.index')" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 flex items-center mt-0">
                            Semua <ArrowRight class="w-3 h-3 ml-1" />
                        </Link>
                    </CardHeader>
                    <CardContent class="pt-4 border-0">
                        <div v-if="recentTransactions.length > 0" class="space-y-3">
                            <div v-for="transaction in recentTransactions" :key="transaction.id" class="flex items-center justify-between p-3 border border-slate-100 dark:border-slate-700/50 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center font-bold text-emerald-700 dark:text-emerald-400 text-xs">
                                        {{ transaction.user?.name?.charAt(0) || 'U' }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ transaction.user?.name }}</span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ transaction.tenancy?.room?.boardingHouse?.name }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="font-bold text-sm text-emerald-600 dark:text-emerald-400">{{ formatRupiah(transaction.amount) }}</span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ new Date(transaction.updated_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-6 text-slate-400 dark:text-slate-500 text-sm">
                            Belum ada riwayat pembayaran.
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- KOLOM KANAN -->
            <div class="space-y-6">
                <!-- Daftar Kamar Kosong -->
                <Card class="relative overflow-hidden border-0 dark:border-0 shadow-sm bg-gradient-to-br from-white to-blue-50/50 dark:bg-slate-900 dark:bg-none hover:shadow-md transition-shadow">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-blue-100/50 dark:bg-blue-500/10 rounded-full blur-3xl -z-10"></div>
                    <CardHeader class="pb-3 border-b border-blue-100/50 dark:border-slate-800">
                        <CardTitle class="text-lg font-bold flex items-center gap-2 text-slate-800 dark:text-white">
                            <div class="p-2 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-lg">
                                <Key class="w-5 h-5" />
                            </div>
                            Kamar Kosong Terbaru
                        </CardTitle>
                        <CardDescription class="dark:text-slate-400">Kamar yang saat ini berstatus tersedia.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-4 border-0">
                        <div v-if="vacantRooms.length > 0" class="space-y-3">
                            <div v-for="room in vacantRooms" :key="room.id" class="group flex items-center justify-between p-3 bg-white dark:bg-slate-800/50 border border-blue-100 dark:border-slate-700/50 shadow-sm rounded-xl hover:border-blue-300 dark:hover:border-blue-500/50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center border border-blue-200 dark:border-slate-600">
                                        <BedDouble class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-sm text-slate-800 dark:text-slate-200 group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors">{{ room.name }} (No. {{ room.room_number }})</span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                                            <MapPin class="w-3 h-3 text-slate-400 dark:text-slate-500"/> {{ room.boarding_house?.name }}
                                        </span>
                                    </div>
                                </div>
                                <Link :href="route('admin.kos.show', { kos: room.boarding_house_id, tab: 'rooms', edit_room: room.id })" class="text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 hover:bg-blue-600 dark:hover:bg-blue-500 hover:text-white px-3 py-1.5 rounded-full transition-colors">
                                    Promosikan
                                </Link>
                            </div>
                        </div>
                        <div v-else class="text-center py-6 text-slate-400 dark:text-slate-500 text-sm flex flex-col items-center">
                            <CheckCircle2 class="w-10 h-10 text-emerald-300 dark:text-emerald-600 mb-2" />
                            <span class="font-medium text-slate-600 dark:text-slate-400">Luar biasa!</span>
                            <span class="mt-1">Semua kamar Anda saat ini terisi.</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Ulasan Terbaru -->
                <Card class="shadow-sm border border-slate-100 dark:border-0 dark:bg-slate-900">
                    <CardHeader class="pb-3 flex flex-row items-center justify-between border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <CardTitle class="text-lg font-bold flex items-center gap-2 text-slate-800 dark:text-white">
                                <MessageSquare class="w-5 h-5 text-teal-500 dark:text-teal-400" /> Ulasan Terbaru
                            </CardTitle>
                            <CardDescription class="dark:text-slate-400">Komentar dan rating dari penyewa.</CardDescription>
                        </div>
                        <Link :href="route('admin.reviews.index')" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300 flex items-center mt-0">
                            Semua <ArrowRight class="w-3 h-3 ml-1" />
                        </Link>
                    </CardHeader>
                    <CardContent class="pt-4 border-0">
                        <div v-if="recentReviews.length > 0" class="space-y-4">
                            <div v-for="review in recentReviews" :key="review.id" class="border-b border-slate-100 dark:border-slate-800 last:border-0 pb-4 last:pb-0">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ review.user?.name }}</span>
                                    <div class="flex items-center text-yellow-400">
                                        <Star v-for="n in 5" :key="n" :class="{'fill-current': n <= review.rating, 'text-slate-200 dark:text-slate-700': n > review.rating}" class="w-3 h-3" />
                                    </div>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2 italic">"{{ review.comment || 'Tidak ada komentar.' }}"</p>
                                <span class="text-[10px] text-slate-500 dark:text-slate-500 font-medium bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded">Properti: {{ review.boarding_house?.name }}</span>
                            </div>
                        </div>
                        <div v-else class="text-center py-6 text-slate-400 dark:text-slate-500 text-sm">
                            Belum ada ulasan dari penyewa.
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
