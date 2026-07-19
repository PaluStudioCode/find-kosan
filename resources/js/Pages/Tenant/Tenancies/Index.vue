<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

defineProps({
    tenancies: Object
});

const formatDate = (date) => {
    return format(new Date(date), 'dd MMM yyyy', { locale: id });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
};
</script>

<template>
    <AppLayout>
        <Head title="Sewa Kos Saya" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Sewa Kos Saya</h2>
            <p class="text-gray-500 mt-1">Daftar kos yang sedang Anda sewa atau dalam proses pengajuan.</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-4">
            <div v-if="tenancies.data.length > 0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Kos & Kamar</TableHead>
                            <TableHead>Mulai Sewa</TableHead>
                            <TableHead>Tagihan Aktif</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="tenancy in tenancies.data" :key="tenancy.id">
                            <TableCell>
                                <div class="font-medium text-gray-900">{{ tenancy.boarding_house?.name }}</div>
                                <div class="text-sm text-gray-500">{{ tenancy.room?.name }} (No. {{ tenancy.room?.room_number }})</div>
                            </TableCell>
                            <TableCell>{{ formatDate(tenancy.start_date) }}</TableCell>
                            <TableCell>
                                <div v-if="tenancy.invoices && tenancy.invoices.length > 0">
                                    <div class="font-semibold text-gray-900">{{ formatPrice(tenancy.invoices[0].amount) }}</div>
                                    <StatusBadge :status="tenancy.invoices[0].status" class="mt-1 inline-flex text-xs" />
                                </div>
                                <span v-else class="text-gray-500 text-sm">-</span>
                            </TableCell>
                            <TableCell>
                                <StatusBadge :status="tenancy.status" />
                            </TableCell>
                            <TableCell class="text-right">
                                <Link :href="route('tenant.tenancies.show', tenancy.id)">
                                    <Button size="sm">Detail & Pembayaran</Button>
                                </Link>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                
                <div v-if="tenancies.links && tenancies.links.length > 3" class="mt-6 flex justify-center gap-1 border-t pt-4">
                    <template v-for="(link, k) in tenancies.links" :key="k">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm border rounded text-gray-400 bg-white" v-html="link.label" />
                        <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-gray-100 bg-white" :class="{ 'bg-gray-900 text-white hover:bg-gray-800 border-gray-900': link.active }" v-html="link.label" />
                    </template>
                </div>
            </div>
            
            <EmptyState 
                v-else
                title="Belum ada penyewaan"
                description="Anda belum menyewa kos atau mengajukan penyewaan."
            />
        </div>
    </AppLayout>
</template>
