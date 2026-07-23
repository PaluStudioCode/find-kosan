<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import StatusBadge from '@/Components/StatusBadge.vue';
import { ChevronLeft, CheckCircle, XCircle, Landmark } from 'lucide-vue-next';

const props = defineProps({ withdrawal: Object });
const reviewForm = useForm({ review_note: '' });
const transferForm = useForm({ transfer_reference: '', transfer_proof: null });
const formatRupiah = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount || 0);

const approve = () => reviewForm.post(route('admin.withdrawals.approve', props.withdrawal.id));
const reject = () => reviewForm.post(route('admin.withdrawals.reject', props.withdrawal.id));
const complete = () => transferForm.post(route('admin.withdrawals.complete', props.withdrawal.id), { forceFormData: true });
</script>

<template>
    <AppLayout>
        <Head :title="`Penarikan #${withdrawal.id}`" />
        <Link :href="route('admin.withdrawals.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-primary mb-5"><ChevronLeft class="w-4 h-4 mr-1" /> Kembali ke penarikan</Link>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div><h2 class="text-2xl font-bold">Penarikan #{{ withdrawal.id }}</h2><p class="text-gray-500">{{ withdrawal.owner?.name }} · {{ withdrawal.owner?.email }}</p></div>
            <StatusBadge :status="withdrawal.status" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
                <CardHeader><CardTitle>Detail Transfer</CardTitle></CardHeader>
                <CardContent class="space-y-4">
                    <div><p class="text-sm text-gray-500">Nominal</p><p class="text-2xl font-bold">{{ formatRupiah(withdrawal.amount) }}</p></div>
                    <div><p class="text-sm text-gray-500">Tujuan</p><p class="font-semibold">{{ withdrawal.bank_name }} — {{ withdrawal.account_number }}</p><p>{{ withdrawal.account_holder_name }}</p></div>
                    <div v-if="withdrawal.owner_note"><p class="text-sm text-gray-500">Catatan pemilik</p><p>{{ withdrawal.owner_note }}</p></div>
                    <div v-if="withdrawal.review_note"><p class="text-sm text-gray-500">Catatan admin</p><p>{{ withdrawal.review_note }}</p></div>
                    <div v-if="withdrawal.transfer_reference"><p class="text-sm text-gray-500">Referensi transfer</p><p class="font-medium">{{ withdrawal.transfer_reference }}</p></div>
                    <a v-if="withdrawal.transfer_proof_path" :href="withdrawal.transfer_proof_path" target="_blank" class="text-primary underline text-sm">Lihat bukti transfer</a>
                </CardContent>
            </Card>

            <Card v-if="withdrawal.status === 'menunggu_persetujuan'">
                <CardHeader><CardTitle>Tinjau Permintaan</CardTitle></CardHeader>
                <CardContent>
                    <Textarea v-model="reviewForm.review_note" rows="4" placeholder="Catatan untuk pemilik (wajib jika ditolak)" />
                    <p v-if="reviewForm.errors.review_note" class="text-sm text-red-600 mt-1">{{ reviewForm.errors.review_note }}</p>
                    <div class="flex gap-3 mt-4">
                        <Button class="flex-1" :disabled="reviewForm.processing" @click="approve"><CheckCircle class="w-4 h-4 mr-2" /> Setujui</Button>
                        <Button class="flex-1" variant="destructive" :disabled="reviewForm.processing" @click="reject"><XCircle class="w-4 h-4 mr-2" /> Tolak</Button>
                    </div>
                </CardContent>
            </Card>

            <Card v-else-if="withdrawal.status === 'disetujui'">
                <CardHeader><CardTitle>Konfirmasi Transfer Manual</CardTitle></CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="complete">
                        <div><Label for="transfer_reference">Nomor referensi transfer</Label><Input id="transfer_reference" v-model="transferForm.transfer_reference" class="mt-1" /><p v-if="transferForm.errors.transfer_reference" class="text-sm text-red-600 mt-1">{{ transferForm.errors.transfer_reference }}</p></div>
                        <div><Label for="transfer_proof">Bukti transfer</Label><Input id="transfer_proof" class="mt-1" type="file" accept="image/*" @change="transferForm.transfer_proof = $event.target.files[0]" /><p v-if="transferForm.errors.transfer_proof" class="text-sm text-red-600 mt-1">{{ transferForm.errors.transfer_proof }}</p></div>
                        <Button class="w-full" :disabled="transferForm.processing"><Landmark class="w-4 h-4 mr-2" /> {{ transferForm.processing ? 'Menyimpan...' : 'Tandai Transfer Selesai' }}</Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
