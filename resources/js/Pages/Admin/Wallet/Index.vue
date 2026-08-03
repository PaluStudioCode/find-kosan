<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { Deferred } from '@inertiajs/vue3';
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
import StatusBadge from '@/components/StatusBadge.vue';
import { Landmark, WalletCards, ArrowDownToLine, Clock, X, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    wallet: Object,
    transactions: Object,
    withdrawals: Object,
    min_withdrawal: Number,
    filters: Object,
});

const filterType = ref(props.filters?.type || '');
const filterMonth = ref(props.filters?.month || '');

const isLoadingTransactions = ref(false);
const isLoadingWithdrawals = ref(false);

watch([filterType, filterMonth], ([type, month]) => {
    isLoadingTransactions.value = true;
    router.get(
        route('admin.wallet.index'),
        { type, month },
        { 
            preserveState: true, 
            preserveScroll: true, 
            replace: true, 
            only: ['transactions'],
            onFinish: () => isLoadingTransactions.value = false
        }
    );
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

const isExporting = ref(false);

const formattedAmount = ref('');

const handleAmountInput = (e) => {
    let rawValue = e.target.value.replace(/\D/g, '');
    if (rawValue) {
        form.amount = rawValue;
        formattedAmount.value = new Intl.NumberFormat('id-ID').format(rawValue);
    } else {
        form.amount = '';
        formattedAmount.value = '';
    }
};

const exportExcel = async () => {
    isExporting.value = true;
    try {
        const response = await axios.get(route('admin.wallet.export'), {
            params: {
                type: filterType.value,
                month: filterMonth.value,
                year: props.filters?.year
            },
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        
        let filename = 'Laporan_Mutasi.xlsx';
        const contentDisposition = response.headers['content-disposition'];
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (filenameMatch && filenameMatch.length === 2) {
                filename = filenameMatch[1];
            }
        }
        
        link.setAttribute('download', filename);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to export excel:', error);
        alert('Gagal mengunduh file Excel. Silakan coba lagi.');
    } finally {
        isExporting.value = false;
    }
};

const submit = () => form.post(route('admin.wallet.withdrawals.store'), {
    preserveScroll: true,
    onSuccess: () => {
        form.reset();
        formattedAmount.value = '';
        isDialogOpen.value = false;
    },
});

</script>

<template>
    <AppLayout>
        <Head title="Saldo & Penarikan" />

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Saldo & Penarikan</h2>
                <p class="text-gray-500 dark:text-slate-400 mt-1">Kelola saldo dari pembayaran sewa yang telah lunas.</p>
            </div>
            
            <Dialog v-model:open="isDialogOpen">
                <DialogTrigger as-child>
                    <Button class="bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 text-white dark:text-emerald-950 shadow-md hover:shadow-lg dark:hover:shadow-emerald-500/20 transition-all font-semibold">
                        <ArrowDownToLine class="w-4 h-4 mr-2" />
                        Ajukan Penarikan
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-[425px] dark:bg-slate-900 dark:border-slate-800">
                    <DialogHeader>
                        <DialogTitle class="dark:text-white">Ajukan Penarikan Saldo</DialogTitle>
                        <DialogDescription class="dark:text-slate-400">
                            Isi detail rekening tujuan untuk penarikan saldo Anda.
                        </DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4 mt-2" @submit.prevent="submit">
                        <div>
                            <Label for="amount" class="text-gray-700 dark:text-slate-300 font-medium">Nominal (Min: {{ formatRupiah(props.min_withdrawal) }})</Label>
                            <Input id="amount" v-model="formattedAmount" @input="handleAmountInput" class="mt-1.5" type="text" placeholder="Contoh: 500.000" />
                            <p v-if="form.errors.amount" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.amount }}</p>
                        </div>
                        <div>
                            <Label for="bank_name" class="text-gray-700 dark:text-slate-300 font-medium">Nama Bank / E-Wallet</Label>
                            <Input id="bank_name" v-model="form.bank_name" class="mt-1.5" placeholder="Contoh: BCA, GoPay" />
                            <p v-if="form.errors.bank_name" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.bank_name }}</p>
                        </div>
                        <div>
                            <Label for="account_number" class="text-gray-700 dark:text-slate-300 font-medium">Nomor Rekening / No. HP</Label>
                            <Input id="account_number" v-model="form.account_number" class="mt-1.5" placeholder="Nomor rekening tujuan" />
                            <p v-if="form.errors.account_number" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.account_number }}</p>
                        </div>
                        <div>
                            <Label for="account_holder_name" class="text-gray-700 dark:text-slate-300 font-medium">Nama Pemilik Rekening</Label>
                            <Input id="account_holder_name" v-model="form.account_holder_name" class="mt-1.5" placeholder="Nama sesuai buku rekening" />
                            <p v-if="form.errors.account_holder_name" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ form.errors.account_holder_name }}</p>
                        </div>
                        <div>
                            <Label for="owner_note" class="text-gray-700 dark:text-slate-300 font-medium">Catatan (opsional)</Label>
                            <Textarea id="owner_note" v-model="form.owner_note" class="mt-1.5" rows="3" placeholder="Tambahkan catatan jika perlu" />
                        </div>
                        <DialogFooter class="pt-4 flex gap-2">
                            <Button type="button" variant="outline" @click="isDialogOpen = false" class="w-full sm:w-auto dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                Batal
                            </Button>
                            <Button type="submit" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 text-white dark:text-emerald-950 font-semibold shadow-md dark:shadow-emerald-500/20 transition-colors" :disabled="form.processing">
                                <Landmark class="w-4 h-4 mr-2" /> {{ form.processing ? 'Mengirim...' : 'Ajukan Penarikan' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <Card class="border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20 shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-emerald-800 dark:text-emerald-400">Saldo Tersedia</CardTitle>
                    <WalletCards class="w-5 h-5 text-emerald-600 dark:text-emerald-500" />
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold text-emerald-700 dark:text-emerald-300">{{ formatRupiah(wallet.available_balance) }}</p>
                    <p class="text-xs text-emerald-700 dark:text-emerald-500 mt-2">Dapat diajukan untuk penarikan.</p>
                </CardContent>
            </Card>
            <Card class="border-orange-200 dark:border-orange-900/50 bg-orange-50/50 dark:bg-orange-950/20 shadow-sm">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-sm font-medium text-orange-800 dark:text-orange-400">Saldo Dalam Proses</CardTitle>
                    <Clock class="w-5 h-5 text-orange-600 dark:text-orange-500" />
                </CardHeader>
                <CardContent>
                    <p class="text-3xl font-bold text-orange-700 dark:text-orange-300">{{ formatRupiah(wallet.pending_withdrawal_balance) }}</p>
                    <p class="text-xs text-orange-700 dark:text-orange-500 mt-2">Menunggu persetujuan atau transfer admin.</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <Card class="shadow-sm">
                <CardHeader><CardTitle>Riwayat Penarikan</CardTitle></CardHeader>
                <CardContent class="p-0">
                    <Deferred data="withdrawals">
                        <template #fallback>
                            <div class="divide-y dark:divide-slate-800">
                                <div v-for="n in 4" :key="'w-skel-'+n" class="p-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between animate-pulse">
                                    <div class="flex-1 space-y-2">
                                        <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-24"></div>
                                        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-3/4 max-w-sm"></div>
                                    </div>
                                    <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded-full w-24"></div>
                                </div>
                            </div>
                        </template>

                        <div v-if="isLoadingWithdrawals" class="divide-y dark:divide-slate-800">
                            <div v-for="n in 4" :key="'w-skel-load-'+n" class="p-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between animate-pulse">
                                <div class="flex-1 space-y-2">
                                    <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-24"></div>
                                    <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-3/4 max-w-sm"></div>
                                </div>
                                <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded-full w-24"></div>
                            </div>
                        </div>

                        <template v-else>
                        <div v-if="withdrawals.data.length" class="divide-y dark:divide-slate-800">
                        <div v-for="withdrawal in withdrawals.data" :key="withdrawal.id" class="p-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                                <div class="flex-1">
                                    <p class="font-semibold dark:text-white">{{ formatRupiah(withdrawal.amount) }}</p>
                                    <p v-if="withdrawal.pph_amount > 0" class="text-xs text-orange-600 dark:text-orange-400 font-medium mt-0.5">Potongan PPh ({{ Number(withdrawal.pph_percent) }}%): -{{ formatRupiah(withdrawal.pph_amount) }}</p>
                                    <p v-if="withdrawal.pph_amount > 0" class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mb-1">Diterima Bersih: {{ formatRupiah(withdrawal.net_amount) }}</p>
                                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ withdrawal.bank_name }} - {{ withdrawal.account_number }} - {{ withdrawal.account_holder_name }}</p>
                                    
                                    <div v-if="withdrawal.status === 'ditolak' && withdrawal.review_note" class="mt-2 p-2.5 bg-red-50 dark:bg-red-900/30 border border-red-100 dark:border-red-900/50 rounded-md">
                                        <p class="text-sm text-red-700 dark:text-red-400 font-medium">Alasan Penolakan:</p>
                                        <p class="text-sm text-red-600 dark:text-red-300">{{ withdrawal.review_note }}</p>
                                    </div>

                                    <div v-if="withdrawal.status === 'selesai'" class="mt-1 flex items-center gap-2">
                                        <p v-if="withdrawal.transfer_reference" class="text-xs text-gray-500 dark:text-slate-400">Ref: {{ withdrawal.transfer_reference }}</p>
                                        <span v-if="withdrawal.transfer_reference && withdrawal.transfer_proof_path" class="text-gray-300 dark:text-slate-700">|</span>
                                        <button v-if="withdrawal.transfer_proof_path" @click="selectedProofImage = withdrawal.transfer_proof_path" class="text-xs text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 hover:underline">Lihat Bukti</button>
                                    </div>
                                </div>
                            <StatusBadge :status="withdrawal.status" />
                        </div>
                    </div>
                    <p v-else class="p-8 text-center text-gray-500 dark:text-slate-400">Belum ada permintaan penarikan.</p>

                    <!-- Pagination for Withdrawals -->
                    <div v-if="withdrawals.links && withdrawals.data.length > 0" class="p-4 border-t border-gray-100 dark:border-slate-800 flex items-center justify-center gap-1 flex-wrap">
                        <template v-for="(link, k) in withdrawals.links" :key="k">
                            <div v-if="link.url === null" class="px-3 py-1 text-sm text-gray-400 border border-transparent" v-html="link.label"></div>
                            <Link v-else :href="link.url" :only="['withdrawals']" preserve-scroll class="px-3 py-1 text-sm border rounded-md transition-colors" :class="link.active ? 'bg-emerald-50 border-emerald-500 text-emerald-700 dark:bg-emerald-900/50 dark:border-emerald-500 dark:text-emerald-300' : 'border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800'" v-html="link.label" @start="isLoadingWithdrawals = true" @finish="isLoadingWithdrawals = false"></Link>
                        </template>
                    </div>
                    </template>
                    </Deferred>
                </CardContent>
            </Card>

            <Card class="shadow-sm">
                <CardHeader class="flex flex-col gap-4">
                    <CardTitle>Mutasi Saldo</CardTitle>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <select v-model="filterType" class="text-sm border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Transaksi</option>
                            <option value="pemasukan">Pemasukan (+)</option>
                            <option value="pengeluaran">Pengeluaran (-)</option>
                        </select>
                        <select v-model="filterMonth" class="text-sm border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Bulan</option>
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">Agustus</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                        <button type="button" @click="exportExcel" :disabled="isExporting"
                           class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md hover:bg-emerald-100 hover:text-emerald-800 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800 dark:hover:bg-emerald-900/50 disabled:opacity-50 disabled:cursor-not-allowed">
                            <Loader2 v-if="isExporting" class="w-4 h-4 animate-spin" />
                            <ArrowDownToLine v-else class="w-4 h-4" />
                            Unduh Excel
                        </button>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <Deferred data="transactions">
                        <template #fallback>
                            <div class="divide-y dark:divide-slate-800">
                                <div v-for="n in 5" :key="'t-skel-'+n" class="p-4 flex items-center justify-between gap-4 animate-pulse">
                                    <div class="space-y-2 flex-1">
                                        <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-48"></div>
                                        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-24"></div>
                                    </div>
                                    <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-20"></div>
                                </div>
                            </div>
                        </template>

                        <div v-if="isLoadingTransactions" class="divide-y dark:divide-slate-800">
                            <div v-for="n in 5" :key="'t-skel-load-'+n" class="p-4 flex items-center justify-between gap-4 animate-pulse">
                                <div class="space-y-2 flex-1">
                                    <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-48"></div>
                                    <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-24"></div>
                                </div>
                                <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-20"></div>
                            </div>
                        </div>

                        <template v-else>
                        <div v-if="transactions.data.length" class="divide-y dark:divide-slate-800">
                        <div v-for="transaction in transactions.data" :key="transaction.id" class="p-4 flex items-center justify-between gap-4">
                            <div>
                                <p class="font-medium dark:text-slate-200">{{ transaction.description }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">{{ new Date(transaction.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</p>
                            </div>
                            <p :class="transaction.type === 'payment_credit' || transaction.type === 'withdrawal_release' ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400'" class="font-bold whitespace-nowrap">
                                {{ transaction.type === 'payment_credit' || transaction.type === 'withdrawal_release' ? '+' : '-' }}{{ formatRupiah(transaction.amount) }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="p-8 text-center text-gray-500 dark:text-slate-400">Belum ada mutasi saldo.</p>

                    <!-- Pagination -->
                    <div v-if="transactions.links && transactions.data.length > 0" class="p-4 border-t border-gray-100 dark:border-slate-800 flex items-center justify-center gap-1 flex-wrap">
                        <template v-for="(link, k) in transactions.links" :key="k">
                            <div v-if="link.url === null" class="px-3 py-1 text-sm text-gray-400 border border-transparent" v-html="link.label"></div>
                            <Link v-else :href="link.url" :only="['transactions']" preserve-scroll class="px-3 py-1 text-sm border rounded-md transition-colors" :class="link.active ? 'bg-emerald-50 border-emerald-500 text-emerald-700 dark:bg-emerald-900/50 dark:border-emerald-500 dark:text-emerald-300' : 'border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-400 hover:bg-gray-50 dark:hover:bg-slate-800'" v-html="link.label" @start="isLoadingTransactions = true" @finish="isLoadingTransactions = false"></Link>
                        </template>
                    </div>
                    </template>
                    </Deferred>
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
