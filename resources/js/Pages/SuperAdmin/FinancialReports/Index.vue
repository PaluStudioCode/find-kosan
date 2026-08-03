<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Deferred } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { FileDown, FileText, CalendarDays, WalletCards, ArrowUpRight, ArrowDownRight, Landmark, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    filters: Object,
    summary: Object,
});

const month = ref(props.filters.month || new Date().getMonth() + 1);
const year = ref(props.filters.year || new Date().getFullYear());

const isLoading = ref(false);

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        route('superadmin.financial-reports.index'),
        { month: month.value, year: year.value },
        { 
            preserveState: true, 
            replace: true,
            only: ['summary'],
            onFinish: () => isLoading.value = false
        }
    );
};

const formatRupiah = (amount) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(amount || 0);

const isDownloadingExcel = ref(false);
const isDownloadingPdf = ref(false);

const downloadExcel = async () => {
    isDownloadingExcel.value = true;
    try {
        const response = await window.axios.get(route('superadmin.financial-reports.export-excel', { month: month.value, year: year.value }), {
            responseType: 'blob'
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Laporan_Keuangan_Platform_${year.value}_${month.value}.xlsx`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Download failed', error);
    } finally {
        isDownloadingExcel.value = false;
    }
};

const downloadPdf = async () => {
    isDownloadingPdf.value = true;
    try {
        const response = await window.axios.get(route('superadmin.financial-reports.export-pdf', { month: month.value, year: year.value }), {
            responseType: 'blob'
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Ringkasan_Keuangan_Platform_${year.value}_${month.value}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Download failed', error);
    } finally {
        isDownloadingPdf.value = false;
    }
};

const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
const yearOptions = [new Date().getFullYear() - 1, new Date().getFullYear(), new Date().getFullYear() + 1];
</script>

<template>
    <AppLayout>
        <Head title="Laporan Keuangan" />

        <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Laporan Keuangan Platform
                </h2>
                <p class="text-gray-500 dark:text-slate-400 mt-1">
                    Rekapitulasi GTV (pendapatan kotor), pajak (PPN & PPh), dan arus kas platform negara.
                </p>
            </div>
            
            <div class="flex items-center gap-2 bg-white dark:bg-slate-800 p-2 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">
                <CalendarDays class="w-5 h-5 text-slate-400 ml-1" />
                <select v-model="month" @change="applyFilters" class="border-none bg-transparent text-sm font-medium focus:ring-0 cursor-pointer dark:text-slate-200">
                    <option v-for="(m, i) in monthNames" :key="i" :value="i + 1">{{ m }}</option>
                </select>
                <span class="text-slate-300 dark:text-slate-600">|</span>
                <select v-model="year" @change="applyFilters" class="border-none bg-transparent text-sm font-medium focus:ring-0 cursor-pointer dark:text-slate-200">
                    <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 mb-8">
            <Button @click="downloadExcel" :disabled="isDownloadingExcel" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow-sm">
                <Loader2 v-if="isDownloadingExcel" class="w-4 h-4 mr-2 animate-spin" />
                <FileDown v-else class="w-4 h-4 mr-2" /> 
                {{ isDownloadingExcel ? 'Mengunduh...' : 'Unduh Laporan Excel' }}
            </Button>

            <Button @click="downloadPdf" :disabled="isDownloadingPdf" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm">
                <Loader2 v-if="isDownloadingPdf" class="w-4 h-4 mr-2 animate-spin" />
                <FileText v-else class="w-4 h-4 mr-2" /> 
                {{ isDownloadingPdf ? 'Mencetak...' : 'Cetak Ringkasan PDF' }}
            </Button>
        </div>

        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Preview Ringkasan Bulan {{ monthNames[month - 1] }} {{ year }}</h3>

        <Deferred data="summary">
            <template #fallback>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
                    <div v-for="i in 6" :key="i" class="h-32 bg-slate-200 dark:bg-slate-800 rounded-xl"></div>
                </div>
            </template>

            <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
                <div v-for="i in 6" :key="'load-'+i" class="h-32 bg-slate-200 dark:bg-slate-800 rounded-xl"></div>
            </div>

            <template v-else>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- GTV -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center justify-between">
                            Gross Transaction Value (GTV)
                            <ArrowUpRight class="w-4 h-4 text-emerald-500" />
                        </CardDescription>
                        <CardTitle class="text-2xl">{{ formatRupiah(summary.gtv) }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Total kotor uang masuk dari penyewa kos.</p>
                    </CardContent>
                </Card>

                <!-- Payouts -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center justify-between">
                            Pencairan Dana (Payouts)
                            <ArrowDownRight class="w-4 h-4 text-orange-500" />
                        </CardDescription>
                        <CardTitle class="text-2xl">{{ formatRupiah(summary.payouts) }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Total bersih ditransfer ke rekening pemilik.</p>
                    </CardContent>
                </Card>

                <!-- PPN -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center justify-between">
                            Total PPN Dipungut
                            <Landmark class="w-4 h-4 text-indigo-500" />
                        </CardDescription>
                        <CardTitle class="text-2xl">{{ formatRupiah(summary.ppn) }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pajak Pertambahan Nilai dari penyewa.</p>
                    </CardContent>
                </Card>

                <!-- PPh -->
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription class="flex items-center justify-between">
                            Total PPh Dipotong
                            <Landmark class="w-4 h-4 text-indigo-500" />
                        </CardDescription>
                        <CardTitle class="text-2xl">{{ formatRupiah(summary.pph) }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pajak Penghasilan dari pencairan pemilik.</p>
                    </CardContent>
                </Card>

                <!-- Net Platform Income -->
                <Card class="bg-slate-900 text-white dark:bg-slate-800 border-none shadow-lg">
                    <CardHeader class="pb-2">
                        <CardDescription class="text-slate-300">
                            Penerimaan Pajak Negara
                        </CardDescription>
                        <CardTitle class="text-3xl text-emerald-400">{{ formatRupiah(summary.net_income) }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-slate-400">Akumulasi PPN + PPh yang siap disetorkan ke negara.</p>
                    </CardContent>
                </Card>
            </div>
            </template>
        </Deferred>

    </AppLayout>
</template>
