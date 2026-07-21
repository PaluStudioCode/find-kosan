<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

const props = defineProps({
    tenancies: Object,
    properties: Array,
    filters: Object
});

const formatDate = (date) => {
    return format(new Date(date), 'dd MMM yyyy', { locale: id });
};

const handleFilterChange = (val) => {
    router.get(route('owner.tenancies.index'), { kos_id: val === 'all' ? null : val }, { preserveState: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Penyewaan" />

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Manajemen Penyewaan</h2>
                <p class="text-gray-500 mt-1">Kelola data penyewa kos dan riwayat transaksinya.</p>
            </div>
            
            <div v-if="properties && properties.length > 0" class="w-full sm:w-64">
                <Select :model-value="filters.kos_id || 'all'" @update:model-value="handleFilterChange">
                    <SelectTrigger class="bg-white">
                        <SelectValue placeholder="Pilih Properti Kos" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Properti Kos</SelectItem>
                        <SelectItem v-for="kos in properties" :key="kos.id" :value="kos.id.toString()">
                            {{ kos.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-4">
            <div v-if="tenancies.data.length > 0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Penyewa</TableHead>
                            <TableHead>Kamar & Kos</TableHead>
                            <TableHead>Mulai Sewa</TableHead>
                            <TableHead>Tagihan Aktif</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="tenancy in tenancies.data" :key="tenancy.id">
                            <TableCell>
                                <div class="font-medium text-gray-900">{{ tenancy.tenant?.name }}</div>
                                <div class="text-xs text-gray-500">{{ tenancy.tenant?.whatsapp_number || tenancy.tenant?.email }}</div>
                            </TableCell>
                            <TableCell>
                                <div class="font-bold text-indigo-900 text-sm mb-1">{{ tenancy.room?.boarding_house?.name || 'Kos' }}</div>
                                <div class="font-medium text-gray-900">{{ tenancy.room?.name }} (No. {{ tenancy.room?.room_number }})</div>
                                <div class="text-xs text-gray-500">Kapasitas: {{ tenancy.occupant_count }} Orang</div>
                            </TableCell>
                            <TableCell>{{ formatDate(tenancy.start_date) }}</TableCell>
                            <TableCell>
                                <div v-if="tenancy.invoices && tenancy.invoices.length > 0">
                                    <StatusBadge :status="tenancy.invoices[0].status" class="inline-flex text-xs" />
                                </div>
                                <span v-else class="text-gray-500 text-sm">-</span>
                            </TableCell>
                            <TableCell>
                                <StatusBadge :status="tenancy.status" />
                            </TableCell>
                            <TableCell class="text-right">
                                <Link :href="route('owner.tenancies.show', tenancy.id)">
                                    <Button size="sm">Tinjau</Button>
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
                description="Belum ada data penyewaan atau transaksi pada properti Anda."
            />
        </div>
    </AppLayout>
</template>
