<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Smartphone, Wifi, WifiOff, QrCode, KeyRound, Loader2, LogOut, RefreshCw } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    session: Object, // { status, phone_number, connected_at, disconnected_at } | null
});

// State
const status = ref(props.session?.status || 'disconnected');
const phoneNumber = ref(props.session?.phone_number || '');
const qrCodeImage = ref(null);
const pairingCode = ref(null);
const isLoading = ref(false);
const connectMethod = ref('qr'); // 'qr' or 'pairing'
const pairingPhoneInput = ref('');
const errorMessage = ref('');
const connectedAt = ref(props.session?.connected_at || null);

let pollInterval = null;

// Computed
const isConnected = computed(() => status.value === 'connected');
const isConnecting = computed(() => status.value === 'connecting');
const isDisconnected = computed(() => status.value === 'disconnected');

const statusLabel = computed(() => {
    if (isConnected.value) return 'Terhubung';
    if (isConnecting.value) return 'Menghubungkan...';
    return 'Tidak Terhubung';
});

const statusVariant = computed(() => {
    if (isConnected.value) return 'default';
    if (isConnecting.value) return 'secondary';
    return 'destructive';
});

// Methods
async function startQrSession() {
    isLoading.value = true;
    errorMessage.value = '';
    connectMethod.value = 'qr';

    try {
        const response = await axios.post(route('owner.whatsapp.start'));
        if (response.data.status === 'already_connected') {
            status.value = 'connected';
            phoneNumber.value = response.data.phoneNumber;
        } else {
            status.value = 'connecting';
            startPolling();
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.error || 'Gagal memulai sesi. Pastikan WA Service berjalan.';
    } finally {
        isLoading.value = false;
    }
}

async function startPairingSession() {
    if (!pairingPhoneInput.value) {
        errorMessage.value = 'Masukkan nomor WhatsApp Anda.';
        return;
    }

    isLoading.value = true;
    errorMessage.value = '';
    connectMethod.value = 'pairing';

    try {
        const response = await axios.post(route('owner.whatsapp.start-pairing'), {
            phone_number: pairingPhoneInput.value,
        });

        if (response.data.status === 'already_connected') {
            status.value = 'connected';
            phoneNumber.value = response.data.phoneNumber;
        } else {
            status.value = 'connecting';
            startPolling();
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || error.response?.data?.error || 'Gagal memulai sesi pairing.';
    } finally {
        isLoading.value = false;
    }
}

async function stopSession() {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        await axios.post(route('owner.whatsapp.stop'));
        status.value = 'disconnected';
        phoneNumber.value = '';
        qrCodeImage.value = null;
        pairingCode.value = null;
        connectedAt.value = null;
        stopPolling();
    } catch (error) {
        errorMessage.value = error.response?.data?.error || 'Gagal memutuskan sesi.';
    } finally {
        isLoading.value = false;
    }
}

async function pollStatus() {
    try {
        // Poll QR code / pairing code
        if (isConnecting.value) {
            const qrResponse = await axios.get(route('owner.whatsapp.qr'));
            const data = qrResponse.data;

            if (data.status === 'connected') {
                status.value = 'connected';
                phoneNumber.value = data.phoneNumber || '';
                qrCodeImage.value = null;
                pairingCode.value = null;
                stopPolling();
                // Refresh connected_at
                const statusRes = await axios.get(route('owner.whatsapp.status'));
                connectedAt.value = statusRes.data.connected_at;
                return;
            }

            if (data.qr) {
                qrCodeImage.value = data.qr;
            }
            if (data.pairingCode) {
                pairingCode.value = data.pairingCode;
            }
        }

        // Periodic status check when connected
        if (isConnected.value) {
            const statusRes = await axios.get(route('owner.whatsapp.status'));
            if (statusRes.data.status === 'disconnected') {
                status.value = 'disconnected';
                phoneNumber.value = '';
                connectedAt.value = null;
            }
        }
    } catch (error) {
        // Silently handle polling errors
        console.warn('Polling error:', error.message);
    }
}

function startPolling() {
    stopPolling();
    pollInterval = setInterval(pollStatus, 3000);
    // Also poll immediately
    pollStatus();
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

onMounted(() => {
    // If currently connecting, start polling
    if (isConnecting.value) {
        startPolling();
    }
    // If connected, poll status every 30s to detect disconnects
    if (isConnected.value) {
        pollInterval = setInterval(pollStatus, 30000);
    }
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <AppLayout>
        <Head title="Pengaturan WhatsApp" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Pengaturan WhatsApp</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-1">Hubungkan akun WhatsApp Anda untuk mengirim notifikasi ke penyewa.</p>
        </div>

        <div class="max-w-3xl mx-auto">
            <Card class="overflow-hidden border-0 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                <CardContent class="p-0 border-0">
                    <!-- Connected State -->
                    <div v-if="isConnected" class="p-8 sm:p-12 text-center flex flex-col items-center bg-white dark:bg-slate-900 rounded-lg">
                        <div class="w-20 h-20 bg-green-50 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-5 ring-8 ring-green-50/50 dark:ring-green-900/20">
                            <Wifi class="w-10 h-10 text-green-500 dark:text-green-400" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">+{{ phoneNumber }}</h3>
                        <p class="text-sm text-gray-500 dark:text-slate-400 mt-2">Terhubung sejak {{ formatDate(connectedAt) }}</p>
                        
                        <div class="mt-8 p-4 bg-gray-50/80 dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-xl text-sm text-gray-600 dark:text-slate-300 max-w-md w-full mx-auto leading-relaxed">
                            Sistem akan otomatis mengirim pengingat tagihan dan konfirmasi pembayaran ke penyewa Anda melalui WhatsApp.
                        </div>

                        <Button variant="destructive" class="mt-8 px-6 shadow-sm" @click="stopSession" :disabled="isLoading">
                            <LogOut class="w-4 h-4 mr-2" />
                            Putuskan Koneksi
                        </Button>
                    </div>

                    <!-- Disconnected State -->
                    <div v-else-if="isDisconnected && !isLoading" class="p-8 sm:p-12 bg-white dark:bg-slate-900 rounded-lg">
                        <div class="max-w-sm mx-auto">
                            <!-- Tabs for connection method -->
                            <div class="flex p-1 bg-gray-100/80 dark:bg-slate-800 rounded-xl mb-10">
                                <button class="flex-1 py-2.5 text-sm font-medium rounded-lg transition-all"
                                        :class="connectMethod === 'qr' ? 'bg-white shadow-sm text-gray-900 dark:bg-slate-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200'"
                                        @click="connectMethod = 'qr'; errorMessage = ''">
                                    Scan QR Code
                                </button>
                                <button class="flex-1 py-2.5 text-sm font-medium rounded-lg transition-all"
                                        :class="connectMethod === 'pairing' ? 'bg-white shadow-sm text-gray-900 dark:bg-slate-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200'"
                                        @click="connectMethod = 'pairing'; errorMessage = ''">
                                    Kode Pairing
                                </button>
                            </div>

                            <div v-if="errorMessage" class="mb-8 p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/50 rounded-xl text-sm text-red-600 dark:text-red-400 flex items-start">
                                <span class="block">{{ errorMessage }}</span>
                            </div>

                            <div v-if="connectMethod === 'qr'" class="text-center animate-in fade-in slide-in-from-bottom-2 duration-300">
                                <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 rounded-2xl mx-auto flex items-center justify-center mb-6 transform rotate-3">
                                    <QrCode class="w-8 h-8 text-blue-500 dark:text-blue-400 -rotate-3" />
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Scan QR Code</h4>
                                <p class="text-sm text-gray-500 dark:text-slate-400 mb-8 leading-relaxed">Gunakan fitur Perangkat Tertaut di aplikasi WhatsApp HP Anda untuk menyambungkan.</p>
                                <Button class="w-full h-11 shadow-sm" @click="startQrSession" :disabled="isLoading">Tampilkan QR Code</Button>
                            </div>

                            <div v-else class="text-center animate-in fade-in slide-in-from-bottom-2 duration-300">
                                <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 rounded-2xl mx-auto flex items-center justify-center mb-6 transform rotate-3">
                                    <KeyRound class="w-8 h-8 text-blue-500 dark:text-blue-400 -rotate-3" />
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kode Pairing</h4>
                                <p class="text-sm text-gray-500 dark:text-slate-400 mb-6 leading-relaxed">Dapatkan 8 digit kode pairing untuk ditautkan dengan nomor HP Anda.</p>
                                
                                <div class="text-left mb-8">
                                    <Label for="pairingPhone" class="text-gray-700 dark:text-slate-300">Nomor WhatsApp</Label>
                                    <Input id="pairingPhone" v-model="pairingPhoneInput" placeholder="Contoh: 08123456789" class="mt-2 h-11 bg-gray-50/50 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500" @keyup.enter="startPairingSession" />
                                </div>
                                <Button class="w-full h-11 shadow-sm" @click="startPairingSession" :disabled="isLoading">Dapatkan Kode</Button>
                            </div>
                        </div>
                    </div>

                    <!-- Connecting State -->
                    <div v-else-if="isConnecting || isLoading" class="p-8 sm:p-12 text-center bg-white dark:bg-slate-900 rounded-lg">
                        <div v-if="connectMethod === 'qr' && qrCodeImage" class="animate-in zoom-in-95 duration-300">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Scan QR Code Ini</h4>
                            <p class="text-sm text-gray-500 dark:text-slate-400 mb-8">Buka <strong>Perangkat Tertaut</strong> di aplikasi WhatsApp Anda.</p>
                            
                            <div class="inline-block p-6 bg-white border border-gray-100 rounded-3xl shadow-sm mb-6">
                                <img :src="qrCodeImage" alt="QR Code" class="w-56 h-56 mx-auto" />
                            </div>
                            <div class="flex flex-col items-center">
                                <p class="text-xs font-medium text-blue-500 dark:text-blue-400 mb-6 flex items-center bg-blue-50 dark:bg-blue-900/30 px-3 py-1.5 rounded-full">
                                    <RefreshCw class="w-3.5 h-3.5 mr-1.5 animate-spin" /> Diperbarui otomatis
                                </p>
                                <Button variant="outline" class="px-6 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="stopSession" :disabled="isLoading">Batalkan</Button>
                            </div>
                        </div>

                        <div v-else-if="connectMethod === 'pairing' && pairingCode" class="animate-in zoom-in-95 duration-300">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kode Pairing Anda</h4>
                            <p class="text-sm text-gray-500 dark:text-slate-400 mb-8">Pilih <strong>Tautkan dengan nomor telepon saja</strong> di WA Anda.</p>
                            
                            <div class="inline-block px-12 py-6 bg-gray-50 dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-3xl mb-8">
                                <span class="text-4xl md:text-5xl font-mono font-bold tracking-[0.25em] text-gray-900 dark:text-white ml-2">{{ pairingCode }}</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <p class="text-sm text-gray-500 dark:text-slate-400 mb-6">Segera masukkan kode ini sebelum kadaluarsa.</p>
                                <Button variant="outline" class="px-6 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="stopSession" :disabled="isLoading">Batalkan</Button>
                            </div>
                        </div>

                        <div v-else class="py-20 animate-in fade-in duration-300">
                            <Loader2 class="w-12 h-12 text-blue-500 dark:text-blue-400 animate-spin mx-auto mb-6" />
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Mempersiapkan Koneksi</h4>
                            <p class="text-sm text-gray-500 dark:text-slate-400 mt-2">Mohon tunggu beberapa detik...</p>
                            <Button variant="outline" class="mt-10 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="stopSession" :disabled="isLoading">Batalkan</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
