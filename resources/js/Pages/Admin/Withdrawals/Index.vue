<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps({ withdrawals: Object });

const formatRupiah = (amount) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(amount || 0);
</script>

<template>
    <AppLayout>
        <Head title="Penarikan Pemilik" />
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Penarikan Pemilik</h2>
            <p class="text-gray-500 mt-1">Tinjau, setujui, lalu selesaikan transfer manual kepada pemilik kos.</p>
        </div>

        <Card>
            <CardHeader><CardTitle>Daftar Permintaan Penarikan</CardTitle></CardHeader>
            <CardContent class="p-0">
                <div v-if="withdrawals.data.length" class="divide-y">
                    <div v-for="withdrawal in withdrawals.data" :key="withdrawal.id" class="p-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="font-bold text-lg">{{ formatRupiah(withdrawal.amount) }}</p>
                            <p class="font-medium">{{ withdrawal.owner?.name }}</p>
                            <p class="text-sm text-gray-500">{{ withdrawal.bank_name }} · {{ withdrawal.account_number }} a.n. {{ withdrawal.account_holder_name }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <StatusBadge :status="withdrawal.status" />
                            <Link :href="route('admin.withdrawals.show', withdrawal.id)"><Button variant="outline">Tinjau</Button></Link>
                        </div>
                    </div>
                </div>
                <p v-else class="p-10 text-center text-gray-500">Belum ada permintaan penarikan.</p>
            </CardContent>
        </Card>
    </AppLayout>
</template>
