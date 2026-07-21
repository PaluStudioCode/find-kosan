<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { FileText, Home, ChevronRight } from 'lucide-vue-next';

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
    <PublicLayout>
        <Head title="Sewa Kos Saya" />
        
        <div class="bg-indigo-600 pb-24 pt-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-white space-y-2">
                <h1 class="text-3xl font-bold">Portal Penyewa</h1>
                <p class="text-indigo-100 opacity-90 max-w-2xl">Selamat datang kembali, {{ $page.props.auth.user.name }}. Kelola penyewaan kos, tagihan, dan riwayat pembayaran Anda di satu tempat.</p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-16 pb-12">
            


            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 gap-8">
                
                <!-- Full Column: Tenancies List -->
                <div class="space-y-8">
                    <Card class="shadow-sm border-0 ring-1 ring-black/5">
                        <CardHeader class="border-b bg-gray-50/50">
                            <CardTitle class="flex items-center gap-2">
                                <Home class="w-5 h-5 text-indigo-600" /> Daftar Kos Saya
                            </CardTitle>
                            <CardDescription>Kos yang sedang Anda sewa atau dalam proses pengajuan.</CardDescription>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="tenancies.data.length > 0">
                                <Table>
                                    <TableHeader>
                                        <TableRow class="bg-gray-50/50">
                                            <TableHead class="pl-6">Properti Kos</TableHead>
                                            <TableHead>Mulai Sewa</TableHead>
                                            <TableHead>Tagihan Aktif</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead class="text-right pr-6">Aksi</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="tenancy in tenancies.data" :key="tenancy.id" class="hover:bg-gray-50/50">
                                            <TableCell class="pl-6">
                                                <div class="font-bold text-gray-900">{{ tenancy.boarding_house?.name }}</div>
                                                <div class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                                    <span class="inline-block w-2 h-2 rounded-full bg-indigo-400"></span> 
                                                    {{ tenancy.room?.name }} (No. {{ tenancy.room?.room_number }})
                                                </div>
                                            </TableCell>
                                            <TableCell class="text-sm text-gray-600">{{ formatDate(tenancy.start_date) }}</TableCell>
                                            <TableCell>
                                                <div v-if="tenancy.status === 'nonaktif' && tenancy.invoices && tenancy.invoices.length > 0">
                                                    <div class="font-bold text-gray-900">{{ formatPrice(tenancy.invoices[0].amount) }}</div>
                                                    <span class="mt-1.5 inline-flex text-[10px] px-2 py-0.5 rounded-full font-semibold bg-indigo-100 text-indigo-700">Wajib Dibayar</span>
                                                    <div class="text-xs text-indigo-600 font-medium mt-1">Pembayaran Sewa Pertama</div>
                                                </div>
                                                <div v-else-if="tenancy.invoices && tenancy.invoices.length > 0">
                                                    <div class="font-bold text-gray-900">{{ formatPrice(tenancy.invoices[0].amount) }}</div>
                                                    <StatusBadge :status="tenancy.invoices[0].status" class="mt-1.5 inline-flex text-[10px] px-2 py-0.5" />
                                                    <div v-if="['belum_dibayar', 'ditolak', 'jatuh_tempo'].includes(tenancy.invoices[0].status)" class="text-xs text-red-500 font-medium mt-1">
                                                        Jatuh Tempo: {{ formatDate(tenancy.invoices[0].due_date) }}
                                                    </div>
                                                </div>
                                                <span v-else class="text-gray-400 text-xs italic">Tidak ada tagihan aktif</span>
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge :status="tenancy.status" class="text-xs" />
                                            </TableCell>
                                            <TableCell class="text-right pr-6">
                                                <Link :href="route('tenant.tenancies.show', tenancy.id)">
                                                    <Button size="sm" variant="outline" class="gap-1 border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                                        Detail <ChevronRight class="w-3 h-3" />
                                                    </Button>
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                                
                                <div v-if="tenancies.links && tenancies.links.length > 3" class="p-4 flex justify-center gap-1 border-t bg-gray-50/50">
                                    <template v-for="(link, k) in tenancies.links" :key="k">
                                        <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-1.5 text-sm border rounded-md text-gray-400 bg-white shadow-sm" v-html="link.label" />
                                        <Link v-else :href="link.url" class="mr-1 mb-1 px-3 py-1.5 text-sm border rounded-md hover:bg-gray-100 bg-white shadow-sm transition-colors" :class="{ 'bg-indigo-600 text-white hover:bg-indigo-700 border-indigo-600': link.active }" v-html="link.label" />
                                    </template>
                                </div>
                            </div>
                            
                            <div v-else class="p-12">
                                <EmptyState 
                                    title="Belum ada penyewaan"
                                    description="Anda belum menyewa kos atau mengajukan penyewaan apapun saat ini."
                                >
                                    <template #action>
                                        <Link :href="route('public.kos.index')">
                                            <Button class="mt-4 bg-indigo-600 hover:bg-indigo-700">Mulai Cari Kos Sekarang</Button>
                                        </Link>
                                    </template>
                                </EmptyState>
                            </div>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </div>
    </PublicLayout>
</template>
