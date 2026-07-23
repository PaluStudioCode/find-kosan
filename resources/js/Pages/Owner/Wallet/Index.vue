<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Landmark, WalletCards, ArrowDownToLine, Clock, X } from 'lucide-vue-next';

const props = defineProps({
    wallet: Object,
    transactions: Object,
    withdrawals: Array,
});

const form = useForm({
    amount: '',
    bank_name: '',
    account_number: '',
    account_holder_name: '',
    owner_note: '',
});

const isDialogOpen = ref(false);
const selectedProofImage = ref(null);

const formatRupiah = (amount) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(amount || 0);

const submit = () => form.post(route('owner.wallet.withdrawals.store'), {
    preserveScroll: true,
    onSuccess: () => {
        form.reset();
        isDialogOpen.value = false;
    },
});

const inputStyles = "mt-1.5 shadow-sm border border-gray-300 hover:border-emerald-400 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 transition-all duration-200 rounded-md bg-white";
</script>

<template>
    <AppLayout>
        <Head title="Saldo & Penarikan" />

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Saldo & Penarikan</h2>
                <p class="text-gray-500 mt-1">Kelola saldo dari pembayaran sewa yang telah lunas.</p>
            </div>
            
            <Dialog v-model:open="isDialogOpen">
                <DialogTrigger as-child>
                    <Button class="bg-emerald-600 hover:bg-emerald-700 text-white shadow-md hover:shadow-lg transition-all">
                        <ArrowDownToLine class="w-4 h-4 mr-2" />
                        Ajukan Penarikan
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Ajukan Penarikan Saldo</DialogTitle>
                        <DialogDescription>
                            Isi detail rekening tujuan untuk penarikan saldo Anda.
                        </DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4 mt-2" @submit.prevent="submit">
                        <div>
                            <Label for="amount" class="text-gray-700 font-medium">Nominal</Label>
                            <Input id="amount" v-model="form.amount" :class="inputStyles" type="number" min="1" placeholder="Contoh: 500000" />
                            <p v-if="form.errors.amount" class="text-sm text-red-600 mt-1">{{ form.errors.amount }}</p>
                        </div>
                        <div>
                            <Label for="bank_name" class="text-gray-700 font-medium">Nama Bank / E-Wallet</Label>
                            <Input id="bank_name" v-model="form.bank_name" :class="inputStyles" placeholder="Contoh: BCA, GoPay" />
                            <p v-if="form.errors.bank_name" class="text-sm text-red-600 mt-1">{{ form.errors.bank_name }}</p>
                        </div>
                        <div>
                            <Label for="account_number" class="text-gray-700 font-medium">Nomor Rekening / No. HP</Label>
                            <Input id="account_number" v-model="form.account_number" :class="inputStyles" placeholder="Nomor rekening tujuan" />
                            <p v-if="form.errors.account_number" class="text-sm text-red-600 mt-1">{{ form.errors.account_number }}</p>
                        </div>
                        <div>
                            <Label for="account_holder_name" class="text-gray-700 font-medium">Nama Pemilik Rekening</Label>
                            <Input id="account_holder_name" v-model="form.account_holder_name" :class="inputStyles" placeholder="Nama sesuai buku rekening" />
                            <p v-if="form.errors.account_holder_name" class="text-sm text-red-600 mt-1">{{ form.errors.account_holder_name }}</p>
                        </div>
                        <div>
                            <Label for="owner_note" class="text-gray-700 font-medium">Catatan (opsional)</Label>
                            <Textarea id="owner_note" v-model="form.owner_note" :class="inputStyles" rows="3" placeholder="Tambahkan catatan jika perlu" />
                        </div>
                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="isDialogOpen = false" class="w-full sm:w-auto">
                                Batal
                            </Button>
                            <Button type="submit" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white shadow-md transition-colors" :disabled="form.processing">
                                <Landmark class="w-4 h-4 mr-2" /> {{ form.processing ? 'Mengirim...' : 'Ajukan Penarikan' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <Card class="border-emerald-200 bg-emerald-50/50 shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-emerald-800">Saldo Tersedia</CardTitle>
                    <WalletCards class="w-5 h-5 text-emerald-600" />
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold text-emerald-700">{{ formatRupiah(wallet.available_balance) }}</p>
                    <p class="text-xs text-emerald-700 mt-2">Dapat diajukan untuk penarikan.</p>
                </CardContent>
            </Card>
            <Card class="border-orange-200 bg-orange-50/50 shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-orange-800">Saldo Dalam Proses</CardTitle>
                    <Clock class="w-5 h-5 text-orange-600" />
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold text-orange-700">{{ formatRupiah(wallet.pending_withdrawal_balance) }}</p>
                    <p class="text-xs text-orange-700 mt-2">Menunggu persetujuan atau transfer admin.</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card class="shadow-sm">
                <CardHeader><CardTitle>Riwayat Penarikan</CardTitle></CardHeader>
                <CardContent class="p-0">
                    <div v-if="withdrawals.length" class="divide-y">
                        <div v-for="withdrawal in withdrawals" :key="withdrawal.id" class="p-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold">{{ formatRupiah(withdrawal.amount) }}</p>
                                    <p class="text-sm text-gray-500">{{ withdrawal.bank_name }} · {{ withdrawal.account_number }} · {{ withdrawal.account_holder_name }}</p>
                                    
                                    <div v-if="withdrawal.status === 'ditolak' && withdrawal.review_note" class="mt-2 p-2.5 bg-red-50 border border-red-100 rounded-md">
                                        <p class="text-sm text-red-700 font-medium">Alasan Penolakan:</p>
                                        <p class="text-sm text-red-600">{{ withdrawal.review_note }}</p>
                                    </div>

                                    <div v-if="withdrawal.status === 'selesai'" class="mt-1 flex items-center gap-2">
                                        <p v-if="withdrawal.transfer_reference" class="text-xs text-gray-500">Ref: {{ withdrawal.transfer_reference }}</p>
                                        <span v-if="withdrawal.transfer_reference && withdrawal.transfer_proof_path" class="text-gray-300">|</span>
                                        <button v-if="withdrawal.transfer_proof_path" @click="selectedProofImage = withdrawal.transfer_proof_path" class="text-xs text-emerald-600 hover:text-emerald-700 hover:underline">Lihat Bukti</button>
                                    </div>
                                </div>
                            <StatusBadge :status="withdrawal.status" />
                        </div>
                    </div>
                    <p v-else class="p-8 text-center text-gray-500">Belum ada permintaan penarikan.</p>
                </CardContent>
            </Card>

            <Card class="shadow-sm">
                <CardHeader><CardTitle>Mutasi Saldo</CardTitle></CardHeader>
                <CardContent class="p-0">
                    <div v-if="transactions.data.length" class="divide-y">
                        <div v-for="transaction in transactions.data" :key="transaction.id" class="p-4 flex items-center justify-between gap-4">
                            <div>
                                <p class="font-medium">{{ transaction.description }}</p>
                                <p class="text-xs text-gray-500">{{ new Date(transaction.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</p>
                            </div>
                            <p :class="transaction.type === 'payment_credit' || transaction.type === 'withdrawal_release' ? 'text-emerald-600' : 'text-orange-600'" class="font-bold whitespace-nowrap">
                                {{ transaction.type === 'payment_credit' || transaction.type === 'withdrawal_release' ? '+' : '-' }}{{ formatRupiah(transaction.amount) }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="p-8 text-center text-gray-500">Belum ada mutasi saldo.</p>
                </CardContent>
            </Card>
        </div>

        <!-- Image Lightbox Modal -->
        <Teleport to="body">
            <div v-if="selectedProofImage" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-md transition-all duration-300" @click="selectedProofImage = null">
                <button class="absolute top-6 right-6 text-white/80 hover:text-white transition-colors" @click="selectedProofImage = null">
                    <X class="w-8 h-8" />
                </button>
                <img :src="selectedProofImage" class="max-w-[90vw] max-h-[90vh] object-contain rounded-lg shadow-2xl" @click.stop />
            </div>
        </Teleport>
    </AppLayout>
</template>
