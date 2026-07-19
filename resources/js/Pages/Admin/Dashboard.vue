<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, FileCheck, Flag } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Button } from '@/components/ui/button';

const props = defineProps({
    metrics: Object,
    recentVerifications: Array,
    recentReports: Array,
});
</script>

<template>
    <AppLayout>
        <Head title="Dashboard Super Admin" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Dashboard Super Admin</h2>
            <p class="text-gray-500 mt-1">Ringkasan sistem secara keseluruhan.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Total Pengguna</CardTitle>
                    <Users class="w-4 h-4 text-primary" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ metrics.totalUsers }}</div>
                    <p class="text-xs text-gray-500 mt-1">{{ metrics.totalOwners }} pemilik, {{ metrics.totalTenants }} penyewa</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Antrean Verifikasi Kos</CardTitle>
                    <FileCheck class="w-4 h-4 text-orange-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-orange-600">{{ metrics.pendingKosVerifications }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-gray-500">Laporan Menunggu Proses</CardTitle>
                    <Flag class="w-4 h-4 text-red-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-600">{{ metrics.pendingReports }}</div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <Card>
                <CardHeader>
                    <CardTitle>Antrean Verifikasi Kos</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="recentVerifications.length > 0" class="space-y-4">
                        <div v-for="kos in recentVerifications" :key="kos.id" class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <p class="font-medium">{{ kos.name }}</p>
                                <p class="text-sm text-gray-500">Pemilik: {{ kos.owner?.name }}</p>
                            </div>
                            <div class="text-right">
                                <StatusBadge :status="kos.status" class="mb-2 block" />
                                <Button size="sm" variant="outline">Tinjau</Button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        Tidak ada antrean verifikasi saat ini.
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Laporan Terbaru</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="recentReports.length > 0" class="space-y-4">
                        <div v-for="report in recentReports" :key="report.id" class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <p class="font-medium">{{ report.title }}</p>
                                <p class="text-sm text-gray-500">Oleh: {{ report.reporter?.name }}</p>
                            </div>
                            <div class="text-right">
                                <StatusBadge :status="report.status" class="mb-2 block" />
                                <Button size="sm" variant="outline">Detail</Button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        Tidak ada laporan yang perlu ditangani.
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
