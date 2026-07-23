<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import StatusBadge from '@/Components/StatusBadge.vue';
import { ChevronLeft, UploadCloud, Clock, CheckCircle, Home, User, Users, Calendar, Hash, Maximize2, Download, CreditCard } from 'lucide-vue-next';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import axios from 'axios';

const props = defineProps({
    tenancy: Object
});

const formatDate = (date) => {
    return format(new Date(date), 'dd MMM yyyy', { locale: id });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
};

const activeInvoice = computed(() => {
    if (!props.tenancy.invoices) return null;
    return props.tenancy.invoices.find(i => ['belum_dibayar', 'jatuh_tempo', 'menunggu_konfirmasi'].includes(i.status));
});

const pastInvoices = computed(() => {
    if (!props.tenancy.invoices) return [];
    return props.tenancy.invoices.filter(i => i.status === 'lunas');
});

const nextBillingDate = computed(() => {
    if (pastInvoices.value.length === 0) {
        if (props.tenancy.invoices && props.tenancy.invoices.length > 0) {
            return props.tenancy.invoices[0].period_end;
        }
        return props.tenancy.start_date;
    }
    const latest = [...pastInvoices.value].sort((a, b) => new Date(b.period_end) - new Date(a.period_end))[0];
    return latest.period_end;
});

const isInitialPayment = computed(() => props.tenancy.status === 'nonaktif');

const isPaying = ref(false);
const paymentSucceeded = ref(false);

const payWithDuitku = async () => {
    if (!activeInvoice.value || isPaying.value) return;

    isPaying.value = true;
    paymentSucceeded.value = false;
    try {
        const response = await axios.post(route('duitku.create-invoice'), {
            invoice_id: activeInvoice.value.id
        });

        const reference = response.data.reference;

        if (typeof checkout === 'undefined') {
            alert('Library Duitku belum dimuat, silakan muat ulang halaman.');
            isPaying.value = false;
            return;
        }

        checkout.process(reference, {
            successEvent: async function(result){
                paymentSucceeded.value = true;
                try {
                    const verification = await axios.post(route('duitku.verify-local'), {
                        reference: reference,
                        merchant_order_id: result.merchantOrderId,
                    });

                    if (verification.data.paid) {
                        router.reload();
                        return;
                    }
                } catch (error) {
                    alert(error.response?.data?.message || 'Pembayaran berhasil, tetapi status belum dapat disinkronkan. Silakan muat ulang halaman dalam beberapa saat.');
                } finally {
                    isPaying.value = false;
                }
            },
            pendingEvent: function(result){
                isPaying.value = false;
                router.reload();
            },
            errorEvent: function(result){
                alert('Pembayaran gagal atau dibatalkan');
                isPaying.value = false;
            },
            closeEvent: function(result){
                if (!paymentSucceeded.value) {
                    isPaying.value = false;
                }
            }
        });
    } catch (error) {
        alert(error.response?.data?.error || 'Terjadi kesalahan saat memproses pembayaran');
        isPaying.value = false;
    }
};

const expirationTime = computed(() => {
    if (!activeInvoice.value || !isInitialPayment.value) return null;
    const createdAt = new Date(activeInvoice.value.created_at);
    return new Date(createdAt.getTime() + 24 * 60 * 60 * 1000);
});

const isOverdue = computed(() => {
    if (!activeInvoice.value || isInitialPayment.value) return false;
    const today = new Date();
    today.setHours(0,0,0,0);
    const dueDate = new Date(activeInvoice.value.due_date);
    return activeInvoice.value.status === 'jatuh_tempo' || today > dueDate;
});

const countdownText = ref('');
const isExpired = ref(false);
let timerInterval = null;

const updateCountdown = () => {
    if (!expirationTime.value) return;
    const now = new Date();
    const diff = expirationTime.value - now;

    if (diff <= 0) {
        countdownText.value = 'Kadaluarsa';
        isExpired.value = true;
        clearInterval(timerInterval);
    } else {
        const hours = Math.floor(diff / (1000 * 60 * 60)).toString().padStart(2, '0');
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
        const seconds = Math.floor((diff % (1000 * 60)) / 1000).toString().padStart(2, '0');
        countdownText.value = `${hours}:${minutes}:${seconds}`;
        isExpired.value = false;
    }
};

onMounted(() => {
    if (!document.querySelector('script[src="https://app-sandbox.duitku.com/lib/js/duitku.js"]')) {
        const script = document.createElement('script');
        script.src = "https://app-sandbox.duitku.com/lib/js/duitku.js";
        document.head.appendChild(script);
    }

    updateCountdown();
    if (expirationTime.value && !isExpired.value) {
        timerInterval = setInterval(updateCountdown, 1000);
    }
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});
</script>

<template>
    <PublicLayout>
        <Head title="Detail Sewa & Pembayaran" />
        
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            
            <!-- Header Section -->
            <div>
                <Link :href="route('tenant.tenancies.index')" class="text-sm text-gray-500 hover:text-indigo-600 flex items-center mb-3 w-fit transition-colors">
                    <ChevronLeft class="w-4 h-4 mr-1" /> Kembali ke Daftar Sewa
                </Link>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h2 class="text-2xl font-extrabold text-gray-900">Detail Sewa & Pembayaran</h2>
                    <StatusBadge :status="tenancy.status" class="w-fit" />
                </div>
            </div>

            <!-- Informasi Kos & Kamar -->
            <Card class="border-gray-100 shadow-sm overflow-hidden">
                <div class="bg-gray-50/50 p-4 border-b border-gray-100 flex items-center gap-2">
                    <Home class="w-5 h-5 text-indigo-500" />
                    <h3 class="font-semibold text-gray-900">Informasi Properti</h3>
                </div>
                <CardContent class="p-0">
                    <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
                        <div class="p-5 space-y-4">
                            <div>
                                <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5 mb-1"><Home class="w-3.5 h-3.5" /> Nama Kos</p>
                                <p class="font-semibold text-gray-900">{{ tenancy.boarding_house?.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5 mb-1"><User class="w-3.5 h-3.5" /> Pemilik / Kontak</p>
                                <p class="font-medium text-gray-900">{{ tenancy.boarding_house?.owner?.name }}</p>
                                <p class="text-sm text-gray-600">{{ tenancy.boarding_house?.public_contact_whatsapp_number || '-' }}</p>
                            </div>
                        </div>
                        <div class="p-5 space-y-4 bg-gray-50/30">
                            <div>
                                <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5 mb-1"><Hash class="w-3.5 h-3.5" /> Kamar</p>
                                <p class="font-semibold text-gray-900">{{ tenancy.room?.name }} (No. {{ tenancy.room?.room_number }})</p>
                            </div>
                            <div class="flex justify-between gap-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5 mb-1"><Users class="w-3.5 h-3.5" /> Penghuni</p>
                                    <p class="font-medium text-gray-900">{{ tenancy.occupant_count }} Orang</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500 flex items-center gap-1.5 mb-1"><Calendar class="w-3.5 h-3.5" /> Masuk</p>
                                    <p class="font-medium text-gray-900">{{ formatDate(tenancy.start_date) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Payment Form Section (Active Invoice) -->
            <Card v-if="activeInvoice" class="border-indigo-200 shadow-md overflow-hidden relative">
                <!-- Highlight line -->
                <div class="absolute top-0 left-0 w-full h-1 bg-indigo-500"></div>
                
                <CardHeader class="bg-indigo-50/50 pb-4">
                    <CardTitle class="text-indigo-900 text-lg flex justify-between items-center">
                        <span>{{ isInitialPayment ? 'Pembayaran Sewa Pertama' : 'Tagihan Pembayaran' }}</span>
                        <StatusBadge v-if="!isInitialPayment" :status="activeInvoice.status" />
                        <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">Wajib Dibayar</span>
                    </CardTitle>
                    <p class="text-sm text-indigo-700 mt-1 font-medium">Periode: {{ formatDate(activeInvoice.period_start) }} - {{ formatDate(activeInvoice.period_end) }}</p>
                </CardHeader>
                
                <CardContent class="pt-6 space-y-6">
                    <!-- Amount Display -->
                    <div class="text-center bg-gray-50 rounded-xl py-6 border border-gray-100 relative overflow-hidden">
                        <div v-if="isInitialPayment && !isExpired" class="absolute top-0 inset-x-0 bg-orange-100 text-orange-800 text-xs py-1.5 font-bold flex justify-center items-center gap-2">
                            <Clock class="w-3.5 h-3.5" /> Selesaikan pembayaran dalam: {{ countdownText }}
                        </div>
                        <div v-else-if="isInitialPayment && isExpired" class="absolute top-0 inset-x-0 bg-red-100 text-red-800 text-xs py-1.5 font-bold flex justify-center items-center gap-2">
                            <Clock class="w-3.5 h-3.5" /> Waktu pembayaran telah habis
                        </div>

                        <p :class="['text-sm text-gray-500 mb-1 font-medium uppercase tracking-wider', isInitialPayment ? 'mt-4' : '']">Total Pembayaran</p>
                        <p class="text-4xl font-extrabold text-gray-900 tracking-tight">{{ formatPrice(activeInvoice.amount) }}</p>
                        <p v-if="isOverdue" class="text-sm text-red-700 mt-3 font-bold bg-red-100 inline-block px-3 py-1 rounded-full border border-red-200">
                            Telah Lewat Jatuh Tempo: {{ formatDate(activeInvoice.due_date) }}
                        </p>
                        <p v-else-if="['belum_dibayar', 'jatuh_tempo'].includes(activeInvoice.status)" class="text-sm text-red-600 mt-3 font-medium bg-red-50 inline-block px-3 py-1 rounded-full border border-red-100">
                            {{ isInitialPayment ? 'Batas Waktu (24 Jam): ' : 'Jatuh Tempo: ' }} {{ formatDate(activeInvoice.due_date) }}
                        </p>
                    </div>
                    
                    <div v-if="['belum_dibayar', 'jatuh_tempo'].includes(activeInvoice.status)" class="space-y-6">
                        
                        <!-- Overdue Warning -->
                        <div v-if="isOverdue" class="bg-red-50 p-4 border border-red-300 rounded-lg shadow-sm">
                            <p class="text-sm font-bold text-red-800 mb-1 flex items-center gap-2">
                                <span class="flex w-5 h-5 rounded-full bg-red-200 text-red-900 items-center justify-center">!</span>
                                Peringatan Jatuh Tempo
                            </p>
                            <p class="text-sm text-red-700 ml-7 leading-relaxed">Tagihan sewa Anda telah melewati batas waktu yang ditentukan. Mohon segera lakukan pembayaran dan unggah bukti untuk menghindari sanksi atau pemutusan sewa dari pemilik kos.</p>
                        </div>

                        <!-- Last rejected note if any -->
                        <div v-if="activeInvoice.payments && activeInvoice.payments.length > 0 && activeInvoice.payments[0].status === 'ditolak'" class="bg-red-50 p-4 border border-red-200 rounded-lg">
                            <p class="text-sm font-bold text-red-800 mb-1 flex items-center gap-2">
                                <span class="flex w-5 h-5 rounded-full bg-red-100 items-center justify-center">!</span>
                                Catatan dari Pemilik Kos
                            </p>
                            <p class="text-sm text-red-700 ml-7">{{ activeInvoice.payments[0].review_note || 'Mohon unggah bukti pembayaran yang lebih jelas atau valid.' }}</p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 max-w-md mx-auto">
                            <!-- Payment Action -->
                            <div class="bg-gray-50 border rounded-xl p-5 text-center shadow-sm">
                                <h4 class="font-bold text-gray-900 mb-2">Metode Pembayaran</h4>
                                <p class="text-sm text-gray-500 mb-6">Pilih dan selesaikan pembayaran Anda menggunakan berbagai metode yang tersedia via Duitku Payment Gateway.</p>

                                <Button v-if="isExpired" type="button" class="w-full h-12 bg-gray-300 text-gray-600 font-medium shadow-sm cursor-not-allowed" disabled>
                                    Booking Kadaluarsa
                                </Button>
                                <Button v-else @click="payWithDuitku" :disabled="isPaying" type="button" class="w-full h-12 bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-sm transition-all text-base">
                                    <CreditCard class="w-5 h-5 mr-2" />
                                    {{ isPaying ? 'Memproses...' : 'Bayar Sekarang' }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Waiting State -->
                    <div v-else-if="activeInvoice.status === 'menunggu_konfirmasi'" class="text-center py-10 bg-orange-50/50 rounded-xl border border-orange-100">
                        <Clock class="w-16 h-16 text-orange-400 mx-auto mb-4 opacity-80" />
                        <h3 class="text-lg font-bold text-orange-900 mb-2">Menunggu Konfirmasi Pemilik</h3>
                        <p class="text-sm text-orange-700 max-w-sm mx-auto">Bukti pembayaran Anda telah diterima dan sedang diverifikasi oleh pemilik kos. Status sewa Anda akan aktif setelah dikonfirmasi.</p>
                    </div>
                </CardContent>
            </Card>
            
            <Card v-else-if="tenancy.status === 'selesai'" class="bg-gradient-to-br from-gray-50 to-slate-100 border-gray-200 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gray-400"></div>
                <CardContent class="py-10">
                    <div class="text-center space-y-3">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto shadow-sm mb-2 border border-gray-100">
                            <CheckCircle class="w-8 h-8 text-gray-500" />
                        </div>
                        <h3 class="text-2xl font-extrabold text-gray-900">Masa Sewa Telah Selesai</h3>
                        <p class="text-gray-600 text-sm max-w-md mx-auto">Terima kasih telah menyewa kamar ini. Riwayat transaksi Anda tetap dapat dilihat di bawah.</p>
                    </div>
                </CardContent>
            </Card>

            <Card v-else class="bg-gradient-to-br from-emerald-50 to-teal-50 border-emerald-200 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-emerald-500"></div>
                <CardContent class="py-10">
                    <div class="text-center space-y-3 mb-8">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto shadow-sm mb-2">
                            <CheckCircle class="w-8 h-8 text-emerald-500" />
                        </div>
                        <h3 class="text-2xl font-extrabold text-emerald-900">Sewa Aktif & Lunas</h3>
                        <p class="text-emerald-700 text-sm max-w-md mx-auto">Anda tidak memiliki tagihan pembayaran yang tertunda saat ini.</p>
                    </div>

                    <div class="bg-white/60 backdrop-blur-sm rounded-xl p-5 border border-emerald-100 max-w-md mx-auto">
                        <h4 class="text-sm font-bold text-emerald-900 mb-3 flex items-center gap-2">
                            <Calendar class="w-4 h-4" /> Informasi Tagihan Berikutnya
                        </h4>
                        <div class="flex justify-between items-center py-2 border-b border-emerald-100/50">
                            <span class="text-sm text-emerald-700">Perkiraan Tanggal</span>
                            <span class="font-semibold text-emerald-900">{{ formatDate(nextBillingDate) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-emerald-700">Jumlah Tagihan</span>
                            <span class="font-bold text-emerald-900">{{ formatPrice(tenancy.room?.price || 0) }}</span>
                        </div>
                        <p class="text-xs text-emerald-600 mt-3 italic text-center">Tagihan akan otomatis muncul di sini saat waktunya tiba.</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Riwayat Pembayaran -->
            <Card v-if="pastInvoices.length > 0" class="border-gray-200">
                <CardHeader class="border-b bg-gray-50/50 pb-4">
                    <CardTitle class="text-gray-800 text-lg flex items-center gap-2">
                        <Clock class="w-5 h-5 text-gray-500" /> Riwayat Transaksi
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="divide-y divide-gray-100">
                        <div v-for="invoice in pastInvoices" :key="invoice.id" class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50 transition-colors">
                            <div>
                                <p class="font-bold text-gray-900 mb-1">Periode: {{ formatDate(invoice.period_start) }} - {{ formatDate(invoice.period_end) }}</p>
                                <p class="text-sm text-gray-500 flex items-center gap-1.5">
                                    <CheckCircle class="w-4 h-4 text-emerald-500" /> 
                                    Berhasil lunas pada {{ invoice.payments && invoice.payments.length ? formatDate(invoice.payments[invoice.payments.length-1].payment_date) : '-' }}
                                </p>
                            </div>
                            <div class="flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2">
                                <p class="font-extrabold text-lg text-gray-900">{{ formatPrice(invoice.amount) }}</p>
                                <StatusBadge :status="invoice.status" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
            
        </div>
    </PublicLayout>
</template>
