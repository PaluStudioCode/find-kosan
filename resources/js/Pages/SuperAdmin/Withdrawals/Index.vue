<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import StatusBadge from '@/components/StatusBadge.vue';

defineProps({ withdrawals: Object });

const formatRupiah = (amount) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(amount || 0);
</script>

<template>
    <AppLayout>
        <Head title="Penarikan Pemilik" />
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Penarikan Pemilik</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-1">Tinjau, setujui, lalu selesaikan transfer manual kepada pemilik kos.</p>
        </div>

        <Card class="border-0 dark:bg-slate-900 shadow-sm dark:border-slate-800">
            <CardHeader class="border-b dark:border-slate-800"><CardTitle class="dark:text-white">Daftar Permintaan Penarikan</CardTitle></CardHeader>
            <CardContent class="p-0 border-0">
                <div v-if="withdrawals.data.length" class="divide-y dark:divide-slate-800">
                    <div v-for="withdrawal in withdrawals.data" :key="withdrawal.id" class="p-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between hover:bg-gray-50/50 dark:hover:bg-slate-800/50 transition-colors">
                        <div>
                            <p class="font-bold text-lg dark:text-slate-200">{{ formatRupiah(withdrawal.amount) }}</p>
                            <p class="font-medium dark:text-slate-300">{{ withdrawal.admin?.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-slate-400">{{ withdrawal.bank_name }} Â· {{ withdrawal.account_number }} a.n. {{ withdrawal.account_holder_name }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <StatusBadge :status="withdrawal.status" />
                            <Link :href="route('superadmin.withdrawals.show', withdrawal.id)"><Button variant="outline" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Tinjau</Button></Link>
                        </div>
                    </div>
                </div>
                <p v-else class="p-10 text-center text-gray-500 dark:text-slate-500">Belum ada permintaan penarikan.</p>
            </CardContent>
        </Card>
    </AppLayout>
</template>
