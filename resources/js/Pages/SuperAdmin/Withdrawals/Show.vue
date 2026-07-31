<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import StatusBadge from '@/components/StatusBadge.vue';
import { ChevronLeft, CheckCircle, XCircle, Landmark } from 'lucide-vue-next';

import { ref } from 'vue';

const props = defineProps({ withdrawal: Object });
const reviewForm = useForm({ review_note: '' });
const transferForm = useForm({ transfer_reference: '', transfer_proof: null });
const formatRupiah = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount || 0);

const isRejecting = ref(false);

const approveAndComplete = () => transferForm.post(route('superadmin.withdrawals.approve', props.withdrawal.id), { forceFormData: true });
const reject = () => reviewForm.post(route('superadmin.withdrawals.reject', props.withdrawal.id));
</script>

<template>
    <AppLayout>
        <Head :title="`Penarikan #${withdrawal.id}`" />
        <Link :href="route('superadmin.withdrawals.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-primary dark:text-slate-400 dark:hover:text-blue-400 mb-5 transition-colors"><ChevronLeft class="w-4 h-4 mr-1" /> Kembali ke penarikan</Link>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div><h2 class="text-2xl font-bold dark:text-white">Penarikan #{{ withdrawal.id }}</h2><p class="text-gray-500 dark:text-slate-400">{{ withdrawal.admin?.name }} Â· {{ withdrawal.admin?.email }}</p></div>
            <StatusBadge :status="withdrawal.status" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card class="border-0 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                <CardHeader class="border-b dark:border-slate-800"><CardTitle class="dark:text-white">Detail Transfer</CardTitle></CardHeader>
                <CardContent class="space-y-4 pt-6">
                    <div><p class="text-sm text-gray-500 dark:text-slate-400">Nominal</p><p class="text-2xl font-bold dark:text-slate-200">{{ formatRupiah(withdrawal.amount) }}</p></div>
                    <div><p class="text-sm text-gray-500 dark:text-slate-400">Tujuan</p><p class="font-semibold dark:text-slate-200">{{ withdrawal.bank_name }} â€” {{ withdrawal.account_number }}</p><p class="dark:text-slate-300">{{ withdrawal.account_holder_name }}</p></div>
                    <div v-if="withdrawal.owner_note"><p class="text-sm text-gray-500 dark:text-slate-400">Catatan pemilik</p><p class="dark:text-slate-300">{{ withdrawal.owner_note }}</p></div>
                    <div v-if="withdrawal.review_note"><p class="text-sm text-gray-500 dark:text-slate-400">Catatan admin</p><p class="dark:text-slate-300">{{ withdrawal.review_note }}</p></div>
                    <div v-if="withdrawal.transfer_reference"><p class="text-sm text-gray-500 dark:text-slate-400">Referensi transfer</p><p class="font-medium dark:text-slate-200">{{ withdrawal.transfer_reference }}</p></div>
                    <a v-if="withdrawal.transfer_proof_path" :href="withdrawal.transfer_proof_path" target="_blank" class="text-primary dark:text-blue-400 underline text-sm inline-block mt-2">Lihat bukti transfer</a>
                </CardContent>
            </Card>

            <Card v-if="withdrawal.status === 'menunggu_persetujuan'" class="border-0 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                <CardHeader class="border-b dark:border-slate-800"><CardTitle class="dark:text-white">Proses Penarikan</CardTitle></CardHeader>
                <CardContent class="pt-6">
                    <form v-if="!isRejecting" class="space-y-4" @submit.prevent="approveAndComplete">
                        <div>
                            <Label for="transfer_reference" class="dark:text-slate-300">Nomor referensi transfer</Label>
                            <Input id="transfer_reference" v-model="transferForm.transfer_reference" class="mt-1 dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                            <p v-if="transferForm.errors.transfer_reference" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ transferForm.errors.transfer_reference }}</p>
                        </div>
                        <div>
                            <Label for="transfer_proof" class="dark:text-slate-300">Bukti transfer</Label>
                            <Input id="transfer_proof" class="mt-1 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 file:text-slate-300" type="file" accept="image/*" @change="transferForm.transfer_proof = $event.target.files[0]" />
                            <p v-if="transferForm.errors.transfer_proof" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ transferForm.errors.transfer_proof }}</p>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-3 mt-6">
                            <Button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500" :disabled="transferForm.processing">
                                <CheckCircle class="w-4 h-4 mr-2" /> Setujui & Transfer
                            </Button>
                            <Button type="button" variant="outline" class="flex-1 text-red-600 hover:bg-red-50 hover:text-red-700 border-red-200 dark:text-red-400 dark:border-red-900/50 dark:hover:bg-red-900/30 dark:hover:text-red-300" @click="isRejecting = true">
                                <XCircle class="w-4 h-4 mr-2" /> Tolak Penarikan
                            </Button>
                        </div>
                    </form>

                    <form v-else class="space-y-4" @submit.prevent="reject">
                        <div>
                            <Label for="review_note" class="text-red-600 dark:text-red-400">Alasan Penolakan</Label>
                            <Textarea id="review_note" v-model="reviewForm.review_note" rows="4" class="mt-1 border-red-300 focus-visible:ring-red-500 dark:bg-slate-800 dark:border-red-900/50 dark:text-white dark:focus-visible:ring-red-900" placeholder="Jelaskan alasan penolakan..." />
                            <p v-if="reviewForm.errors.review_note" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ reviewForm.errors.review_note }}</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 mt-6">
                            <Button type="submit" variant="destructive" class="flex-1" :disabled="reviewForm.processing">
                                <XCircle class="w-4 h-4 mr-2" /> Konfirmasi Tolak
                            </Button>
                            <Button type="button" variant="outline" class="flex-1 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="isRejecting = false">
                                Batal
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
