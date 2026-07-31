<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import StatusBadge from '@/components/StatusBadge.vue';
import EmptyState from '@/components/EmptyState.vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { Home, ChevronRight, Calendar, MapPin, Receipt, ArrowRight } from 'lucide-vue-next';

defineProps({
    tenancies: Object,
    metrics: Object,
    recentInvoices: Array,
    recentPayments: Array,
});

const formatDate = (date) => {
    return format(new Date(date), 'dd MMM yyyy', { locale: id });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
};
</script>

<template>
    <PublicLayout hide-footer>
        <Head title="Sewa Kos Saya" />
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Main Content -->
            <div class="space-y-6">
                
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                        <Home class="w-5 h-5 text-teal-600" /> Daftar Kos Saya
                    </h2>
                </div>

                <div v-if="tenancies.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="tenancy in tenancies.data" 
                        :key="tenancy.id" 
                        class="group bg-white border border-slate-200 rounded-2xl p-6 hover:shadow-lg hover:border-teal-300 transition-all duration-300 flex flex-col relative overflow-hidden"
                    >
                        <!-- Top left border accent -->
                        <div class="absolute top-0 left-0 w-full h-1" 
                             :class="{
                                'bg-emerald-500': tenancy.status === 'aktif',
                                'bg-amber-500': tenancy.status === 'pengajuan',
                                'bg-slate-300': tenancy.status === 'selesai' || tenancy.status === 'nonaktif' || tenancy.status === 'ditolak'
                             }">
                        </div>

                        <!-- Header: Name & Status -->
                        <div class="flex items-start justify-between gap-4 mb-5 mt-1">
                            <div>
                                <h3 class="font-bold text-lg text-slate-900 leading-tight">{{ tenancy.boarding_house?.name }}</h3>
                                <p class="text-sm font-medium text-slate-500 mt-1 flex items-center gap-1.5">
                                    <MapPin class="w-4 h-4 text-slate-400" /> {{ tenancy.room?.name }} (No. {{ tenancy.room?.room_number }})
                                </p>
                            </div>
                            <StatusBadge :status="tenancy.status" class="shrink-0" />
                        </div>

                        <!-- Divider -->
                        <div class="h-px bg-slate-100 w-full mb-5"></div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <Calendar class="w-3.5 h-3.5" /> Mulai Sewa
                                </p>
                                <p class="text-sm font-semibold text-slate-800">{{ formatDate(tenancy.start_date) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                                    <Receipt class="w-3.5 h-3.5" /> Tagihan
                                </p>
                                
                                <!-- Invoices logic -->
                                <div v-if="tenancy.status === 'nonaktif' && tenancy.invoices && tenancy.invoices.length > 0">
                                    <p class="text-sm font-bold text-slate-900">{{ formatPrice(tenancy.invoices[0].amount) }}</p>
                                    <span class="inline-block text-[10px] bg-red-50 text-red-600 font-bold px-2 py-0.5 rounded-full mt-1">Wajib Dibayar</span>
                                </div>
                                <div v-else-if="tenancy.invoices && tenancy.invoices.length > 0">
                                    <p class="text-sm font-bold text-slate-900">{{ formatPrice(tenancy.invoices[0].amount) }}</p>
                                    <StatusBadge :status="tenancy.invoices[0].status" class="mt-1 !text-[10px] !px-2 !py-0.5" />
                                </div>
                                <div v-else>
                                    <span class="text-xs text-slate-400 italic">Tidak ada tagihan</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <div class="mt-auto pt-2">
                            <Link :href="route('user.tenancies.show', tenancy.id)" class="block">
                                <Button class="group/btn w-full bg-slate-50 hover:bg-teal-600 text-slate-700 hover:text-white border border-slate-200 hover:border-teal-600 font-bold transition-all shadow-sm hover:shadow">
                                    Lihat Detail Penyewaan
                                    <ArrowRight class="w-4 h-4 ml-2 transition-transform group-hover/btn:translate-x-1" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="tenancies.data.length > 0 && tenancies.links && tenancies.links.length > 3" class="flex justify-center gap-1 mt-8">
                    <template v-for="(link, k) in tenancies.links" :key="k">
                        <div v-if="link.url === null" class="px-3.5 py-2 text-sm border border-slate-200 rounded-lg text-slate-400 bg-slate-50 font-medium" v-html="link.label" />
                        <Link v-else :href="link.url" class="px-3.5 py-2 text-sm border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-700 font-medium transition-colors" :class="{ '!bg-teal-600 !border-teal-600 !text-white hover:!bg-teal-700': link.active }" v-html="link.label" />
                    </template>
                </div>
                
                <!-- Empty State -->
                <div v-if="tenancies.data.length === 0" class="py-12 bg-white border border-slate-200 rounded-3xl">
                    <EmptyState 
                        title="Belum ada penyewaan"
                        description="Anda belum menyewa kos atau mengajukan penyewaan apapun saat ini."
                    >
                        <template #action>
                            <Link :href="route('public.kos.index')">
                                <Button class="mt-4 bg-teal-600 hover:bg-teal-700 font-bold h-11 px-6 rounded-full shadow-md hover:-translate-y-0.5 transition-all">Mulai Cari Kos Sekarang</Button>
                            </Link>
                        </template>
                    </EmptyState>
                </div>

            </div>
        </div>
    </PublicLayout>
</template>
