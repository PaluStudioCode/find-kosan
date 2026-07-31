<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Settings, Save, Upload, Smartphone, Wifi, QrCode, KeyRound, Loader2, LogOut, RefreshCw, Landmark, Globe, Scale } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    settings: Object,
    wa_session: Object
});

// --- SETTINGS FORM ---
const form = useForm({
    app_name: props.settings.app_name || '',
    footer_text: props.settings.footer_text || '',
    contact_email: props.settings.contact_email || '',
    contact_phone: props.settings.contact_phone || '',
    app_logo: null,
    
    fee_percent: props.settings.fee_percent || '',
    min_withdrawal: props.settings.min_withdrawal || '',
    
    link_instagram: props.settings.link_instagram || '',
    link_facebook: props.settings.link_facebook || '',
    link_tiktok: props.settings.link_tiktok || '',
    meta_description: props.settings.meta_description || '',
    og_image: null,
    
    about_us: props.settings.about_us || '',
    terms_conditions: props.settings.terms_conditions || '',
    privacy_policy: props.settings.privacy_policy || '',
});

const logoPreview = ref(props.settings.app_logo ? '/storage/' + props.settings.app_logo : null);
const ogPreview = ref(props.settings.og_image ? '/storage/' + props.settings.og_image : null);

const handleLogoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.app_logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const handleOgUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.og_image = file;
        ogPreview.value = URL.createObjectURL(file);
    }
};

const submitSettings = () => {
    form.post(route('superadmin.settings.update'), {
        preserveScroll: true
    });
};

// --- WHATSAPP SETTINGS ---
const status = ref(props.wa_session?.status || 'disconnected');
const phoneNumber = ref(props.wa_session?.phone_number || '');
const qrCodeImage = ref(null);
const pairingCode = ref(null);
const isLoading = ref(false);
const connectMethod = ref('qr'); 
const pairingPhoneInput = ref('');
const errorMessage = ref('');
const connectedAt = ref(props.wa_session?.connected_at || null);

let pollInterval = null;

const isConnected = computed(() => status.value === 'connected');
const isConnecting = computed(() => status.value === 'connecting');
const isDisconnected = computed(() => status.value === 'disconnected');

async function startQrSession() {
    isLoading.value = true;
    errorMessage.value = '';
    connectMethod.value = 'qr';

    try {
        const response = await axios.post(route('superadmin.whatsapp.start'));
        if (response.data.status === 'already_connected') {
            status.value = 'connected';
            phoneNumber.value = response.data.phoneNumber;
        } else {
            status.value = 'connecting';
            startPolling();
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.error || 'Gagal memulai sesi.';
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
        const response = await axios.post(route('superadmin.whatsapp.start-pairing'), {
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
        errorMessage.value = error.response?.data?.message || 'Gagal memulai sesi pairing.';
    } finally {
        isLoading.value = false;
    }
}

async function stopSession() {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        await axios.post(route('superadmin.whatsapp.stop'));
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
        if (isConnecting.value) {
            const qrResponse = await axios.get(route('superadmin.whatsapp.qr'));
            const data = qrResponse.data;

            if (data.status === 'connected') {
                status.value = 'connected';
                phoneNumber.value = data.phoneNumber || '';
                qrCodeImage.value = null;
                pairingCode.value = null;
                stopPolling();
                const statusRes = await axios.get(route('superadmin.whatsapp.status'));
                connectedAt.value = statusRes.data.connected_at;
                return;
            }
            if (data.qr) qrCodeImage.value = data.qr;
            if (data.pairingCode) pairingCode.value = data.pairingCode;
        }

        if (isConnected.value) {
            const statusRes = await axios.get(route('superadmin.whatsapp.status'));
            if (statusRes.data.status === 'disconnected') {
                status.value = 'disconnected';
                phoneNumber.value = '';
                connectedAt.value = null;
            }
        }
    } catch (error) {
        console.warn('Polling error:', error.message);
    }
}

function startPolling() {
    stopPolling();
    pollInterval = setInterval(pollStatus, 3000);
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
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

onMounted(() => {
    if (isConnecting.value) startPolling();
    if (isConnected.value) pollInterval = setInterval(pollStatus, 30000);
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <AppLayout>
        <Head title="Pengaturan Sistem" />

        <div class="mb-6 flex items-center gap-2">
            <Settings class="w-6 h-6 text-slate-800 dark:text-slate-200" />
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Pengaturan Sistem</h2>
        </div>

        <div class="max-w-5xl">
            <Tabs defaultValue="general" class="w-full flex flex-col md:flex-row gap-6 items-start">
                
                <TabsList class="flex flex-col h-auto w-full md:w-64 bg-transparent space-y-1 p-0">
                    <TabsTrigger value="general" class="w-full justify-start px-4 py-2.5 data-[state=active]:bg-white dark:data-[state=active]:bg-slate-800 data-[state=active]:shadow-sm rounded-lg border border-transparent data-[state=active]:border-slate-200 dark:data-[state=active]:border-slate-700 transition-all">
                        <Settings class="w-4 h-4 mr-2" /> Umum & Branding
                    </TabsTrigger>
                    <TabsTrigger value="finance" class="w-full justify-start px-4 py-2.5 data-[state=active]:bg-white dark:data-[state=active]:bg-slate-800 data-[state=active]:shadow-sm rounded-lg border border-transparent data-[state=active]:border-slate-200 dark:data-[state=active]:border-slate-700 transition-all">
                        <Landmark class="w-4 h-4 mr-2" /> Finansial & Transaksi
                    </TabsTrigger>
                    <TabsTrigger value="seo" class="w-full justify-start px-4 py-2.5 data-[state=active]:bg-white dark:data-[state=active]:bg-slate-800 data-[state=active]:shadow-sm rounded-lg border border-transparent data-[state=active]:border-slate-200 dark:data-[state=active]:border-slate-700 transition-all">
                        <Globe class="w-4 h-4 mr-2" /> Sosial Media & SEO
                    </TabsTrigger>
                    <TabsTrigger value="legal" class="w-full justify-start px-4 py-2.5 data-[state=active]:bg-white dark:data-[state=active]:bg-slate-800 data-[state=active]:shadow-sm rounded-lg border border-transparent data-[state=active]:border-slate-200 dark:data-[state=active]:border-slate-700 transition-all">
                        <Scale class="w-4 h-4 mr-2" /> Halaman Legal
                    </TabsTrigger>
                    <TabsTrigger value="whatsapp" class="w-full justify-start px-4 py-2.5 data-[state=active]:bg-white dark:data-[state=active]:bg-slate-800 data-[state=active]:shadow-sm rounded-lg border border-transparent data-[state=active]:border-slate-200 dark:data-[state=active]:border-slate-700 transition-all">
                        <Smartphone class="w-4 h-4 mr-2" /> WhatsApp Sistem
                    </TabsTrigger>
                </TabsList>

                <div class="flex-1 w-full">
                    <!-- GENERAL SETTINGS TAB -->
                    <TabsContent value="general" class="m-0 focus-visible:outline-none focus-visible:ring-0">
                        <Card class="border-0 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                            <CardHeader>
                                <CardTitle>Umum & Branding</CardTitle>
                                <CardDescription>Sesuaikan identitas dan branding aplikasi.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="submitSettings" class="space-y-6">
                                    <!-- Application Name -->
                                    <div class="space-y-2">
                                        <Label for="app_name">Nama Aplikasi</Label>
                                        <Input id="app_name" type="text" v-model="form.app_name" placeholder="Misal: Find Kosan" />
                                        <p v-if="form.errors.app_name" class="text-xs text-red-500">{{ form.errors.app_name }}</p>
                                    </div>

                                    <!-- Logo Upload -->
                                    <div class="space-y-2">
                                        <Label for="app_logo">Logo Aplikasi</Label>
                                        <div class="flex items-center gap-4 mt-2">
                                            <div class="h-20 w-20 flex-shrink-0 rounded-lg border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex items-center justify-center overflow-hidden">
                                                <img v-if="logoPreview" :src="logoPreview" alt="Logo Preview" class="object-contain h-full w-full p-1" />
                                                <Upload v-else class="w-6 h-6 text-slate-400" />
                                            </div>
                                            <div class="flex-1">
                                                <Input id="app_logo" type="file" accept="image/*" @change="handleLogoUpload" class="cursor-pointer" />
                                                <p class="text-xs text-slate-500 mt-1">Format: JPG, PNG, SVG (Maks. 2MB)</p>
                                            </div>
                                        </div>
                                        <p v-if="form.errors.app_logo" class="text-xs text-red-500">{{ form.errors.app_logo }}</p>
                                    </div>

                                    <!-- Footer Text -->
                                    <div class="space-y-2">
                                        <Label for="footer_text">Teks Copyright / Footer</Label>
                                        <Input id="footer_text" type="text" v-model="form.footer_text" placeholder="Misal: All rights reserved." />
                                        <p v-if="form.errors.footer_text" class="text-xs text-red-500">{{ form.errors.footer_text }}</p>
                                    </div>

                                    <!-- Contact Email -->
                                    <div class="space-y-2">
                                        <Label for="contact_email">Email Kontak (Support)</Label>
                                        <Input id="contact_email" type="email" v-model="form.contact_email" placeholder="Misal: support@kosonline.com" />
                                        <p v-if="form.errors.contact_email" class="text-xs text-red-500">{{ form.errors.contact_email }}</p>
                                    </div>

                                    <!-- Contact Phone -->
                                    <div class="space-y-2">
                                        <Label for="contact_phone">Nomor Telepon/WhatsApp (Kontak)</Label>
                                        <Input id="contact_phone" type="text" v-model="form.contact_phone" placeholder="Misal: 08123456789" />
                                        <p v-if="form.errors.contact_phone" class="text-xs text-red-500">{{ form.errors.contact_phone }}</p>
                                    </div>

                                    <div class="flex justify-end pt-4 border-t dark:border-slate-800">
                                        <Button type="submit" :disabled="form.processing" class="gap-2">
                                            <Save class="w-4 h-4" /> Simpan Pengaturan
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- FINANCE SETTINGS TAB -->
                    <TabsContent value="finance" class="m-0 focus-visible:outline-none focus-visible:ring-0">
                        <Card class="border-0 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                            <CardHeader>
                                <CardTitle>Finansial & Transaksi</CardTitle>
                                <CardDescription>Atur potongan biaya admin dan penarikan pemilik.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="submitSettings" class="space-y-6">
                                    <div class="space-y-2">
                                        <Label for="fee_percent">Biaya Admin (Persentase %)</Label>
                                        <div class="relative">
                                            <Input id="fee_percent" type="number" step="0.1" v-model="form.fee_percent" placeholder="Misal: 5" class="pr-8" />
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-500">%</div>
                                        </div>
                                        <p class="text-xs text-slate-500">Potongan dari harga sewa setiap terjadi transaksi sukses.</p>
                                        <p v-if="form.errors.fee_percent" class="text-xs text-red-500">{{ form.errors.fee_percent }}</p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="min_withdrawal">Minimal Penarikan Dana (Rp)</Label>
                                        <Input id="min_withdrawal" type="number" v-model="form.min_withdrawal" placeholder="Misal: 50000" />
                                        <p class="text-xs text-slate-500">Saldo minimum agar pemilik bisa request penarikan.</p>
                                        <p v-if="form.errors.min_withdrawal" class="text-xs text-red-500">{{ form.errors.min_withdrawal }}</p>
                                    </div>

                                    <div class="flex justify-end pt-4 border-t dark:border-slate-800">
                                        <Button type="submit" :disabled="form.processing" class="gap-2">
                                            <Save class="w-4 h-4" /> Simpan Pengaturan
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- SEO & SOCIAL SETTINGS TAB -->
                    <TabsContent value="seo" class="m-0 focus-visible:outline-none focus-visible:ring-0">
                        <Card class="border-0 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                            <CardHeader>
                                <CardTitle>Sosial Media & SEO</CardTitle>
                                <CardDescription>Kelola tautan sosmed dan data meta untuk mesin pencari.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="submitSettings" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <Label for="link_instagram">Link Instagram</Label>
                                            <Input id="link_instagram" type="url" v-model="form.link_instagram" placeholder="https://instagram.com/..." />
                                        </div>
                                        <div class="space-y-2">
                                            <Label for="link_facebook">Link Facebook</Label>
                                            <Input id="link_facebook" type="url" v-model="form.link_facebook" placeholder="https://facebook.com/..." />
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <Label for="link_tiktok">Link TikTok</Label>
                                            <Input id="link_tiktok" type="url" v-model="form.link_tiktok" placeholder="https://tiktok.com/..." />
                                        </div>
                                    </div>

                                    <div class="space-y-2 pt-4 border-t dark:border-slate-800">
                                        <Label for="meta_description">Meta Description (SEO)</Label>
                                        <Textarea id="meta_description" v-model="form.meta_description" placeholder="Platform cari kos terbaik dan terpercaya..." class="resize-none h-20" />
                                        <p class="text-xs text-slate-500">Teks ini akan muncul di hasil pencarian Google.</p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="og_image">Gambar Preview Sosial Media (OG Image)</Label>
                                        <div class="flex items-start gap-4 mt-2">
                                            <div class="h-24 w-40 flex-shrink-0 rounded-lg border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex items-center justify-center overflow-hidden">
                                                <img v-if="ogPreview" :src="ogPreview" alt="OG Preview" class="object-cover h-full w-full" />
                                                <Globe v-else class="w-8 h-8 text-slate-400" />
                                            </div>
                                            <div class="flex-1">
                                                <Input id="og_image" type="file" accept="image/jpeg,image/png" @change="handleOgUpload" class="cursor-pointer" />
                                                <p class="text-xs text-slate-500 mt-1">Muncul saat link website dibagikan di WhatsApp/Facebook. (Disarankan 1200x630px)</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-end pt-4 border-t dark:border-slate-800">
                                        <Button type="submit" :disabled="form.processing" class="gap-2">
                                            <Save class="w-4 h-4" /> Simpan Pengaturan
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- LEGAL PAGES SETTINGS TAB -->
                    <TabsContent value="legal" class="m-0 focus-visible:outline-none focus-visible:ring-0">
                        <Card class="border-0 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                            <CardHeader>
                                <CardTitle>Halaman Legal</CardTitle>
                                <CardDescription>Isi teks untuk halaman statis seperti Syarat Ketentuan.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="submitSettings" class="space-y-6">
                                    <Tabs defaultValue="about_us" class="w-full">
                                        <TabsList class="grid w-full grid-cols-3">
                                            <TabsTrigger value="about_us">Tentang Kami</TabsTrigger>
                                            <TabsTrigger value="terms_conditions">Syarat & Ketentuan</TabsTrigger>
                                            <TabsTrigger value="privacy_policy">Kebijakan Privasi</TabsTrigger>
                                        </TabsList>
                                        <TabsContent value="about_us" class="pt-4">
                                            <div class="space-y-2">
                                                <Label for="about_us" class="sr-only">Tentang Kami</Label>
                                                <Textarea id="about_us" v-model="form.about_us" class="min-h-[400px] text-sm" placeholder="Tulis cerita atau profil singkat perusahaan Anda di sini... (Mendukung format paragraf biasa)" />
                                            </div>
                                        </TabsContent>
                                        <TabsContent value="terms_conditions" class="pt-4">
                                            <div class="space-y-2">
                                                <Label for="terms_conditions" class="sr-only">Syarat & Ketentuan</Label>
                                                <Textarea id="terms_conditions" v-model="form.terms_conditions" class="min-h-[400px] text-sm" placeholder="1. Definisi&#10;2. Kewajiban Pengguna..." />
                                            </div>
                                        </TabsContent>
                                        <TabsContent value="privacy_policy" class="pt-4">
                                            <div class="space-y-2">
                                                <Label for="privacy_policy" class="sr-only">Kebijakan Privasi</Label>
                                                <Textarea id="privacy_policy" v-model="form.privacy_policy" class="min-h-[400px] text-sm" placeholder="Data apa saja yang kami kumpulkan dan bagaimana kami mengelolanya..." />
                                            </div>
                                        </TabsContent>
                                    </Tabs>

                                    <div class="flex justify-end pt-4 border-t dark:border-slate-800">
                                        <Button type="submit" :disabled="form.processing" class="gap-2">
                                            <Save class="w-4 h-4" /> Simpan Pengaturan
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- WHATSAPP SETTINGS TAB -->
                    <TabsContent value="whatsapp" class="m-0 focus-visible:outline-none focus-visible:ring-0">
                        <Card class="border-0 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                            <CardHeader>
                                <CardTitle>WhatsApp Sistem</CardTitle>
                                <CardDescription>Hubungkan nomor WhatsApp untuk mengirim notifikasi otomatis.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <!-- Connected State -->
                                <div v-if="isConnected" class="p-8 sm:p-12 text-center flex flex-col items-center bg-white dark:bg-slate-900 rounded-lg">
                                    <div class="w-20 h-20 bg-green-50 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-5 ring-8 ring-green-50/50 dark:ring-green-900/20">
                                        <Wifi class="w-10 h-10 text-green-500 dark:text-green-400" />
                                    </div>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">+{{ phoneNumber }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-slate-400 mt-2">Terhubung sejak {{ formatDate(connectedAt) }}</p>
                                    
                                    <div class="mt-8 p-4 bg-gray-50/80 dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-xl text-sm text-gray-600 dark:text-slate-300 max-w-md w-full mx-auto leading-relaxed">
                                        Sistem akan otomatis mengirim pengingat, konfirmasi pembayaran dan notifikasi ke pengguna menggunakan nomor ini.
                                    </div>

                                    <Button variant="destructive" class="mt-8 px-6 shadow-sm" @click="stopSession" :disabled="isLoading">
                                        <LogOut class="w-4 h-4 mr-2" />
                                        Putuskan Koneksi
                                    </Button>
                                </div>

                                <!-- Disconnected State -->
                                <div v-else-if="isDisconnected && !isLoading" class="p-8 sm:p-12 bg-white dark:bg-slate-900 rounded-lg border border-slate-100 dark:border-slate-800">
                                    <div class="max-w-sm mx-auto">
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

                                        <div v-if="connectMethod === 'qr'" class="text-center animate-in fade-in duration-300">
                                            <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 rounded-2xl mx-auto flex items-center justify-center mb-6 transform rotate-3">
                                                <QrCode class="w-8 h-8 text-blue-500 dark:text-blue-400 -rotate-3" />
                                            </div>
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Scan QR Code</h4>
                                            <p class="text-sm text-gray-500 dark:text-slate-400 mb-8 leading-relaxed">Gunakan fitur Perangkat Tertaut di WhatsApp Anda untuk menyambungkan.</p>
                                            <Button class="w-full h-11 shadow-sm" @click="startQrSession" :disabled="isLoading">Tampilkan QR Code</Button>
                                        </div>

                                        <div v-else class="text-center animate-in fade-in duration-300">
                                            <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 rounded-2xl mx-auto flex items-center justify-center mb-6 transform rotate-3">
                                                <KeyRound class="w-8 h-8 text-blue-500 dark:text-blue-400 -rotate-3" />
                                            </div>
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kode Pairing</h4>
                                            <p class="text-sm text-gray-500 dark:text-slate-400 mb-6 leading-relaxed">Dapatkan 8 digit kode pairing.</p>
                                            
                                            <div class="text-left mb-8">
                                                <Label for="pairingPhone">Nomor WhatsApp</Label>
                                                <Input id="pairingPhone" v-model="pairingPhoneInput" placeholder="Contoh: 08123456789" class="mt-2 h-11" @keyup.enter="startPairingSession" />
                                            </div>
                                            <Button class="w-full h-11 shadow-sm" @click="startPairingSession" :disabled="isLoading">Dapatkan Kode</Button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Connecting State -->
                                <div v-else-if="isConnecting || isLoading" class="p-8 sm:p-12 text-center bg-white dark:bg-slate-900 rounded-lg">
                                    <div v-if="connectMethod === 'qr' && qrCodeImage" class="animate-in zoom-in-95 duration-300">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Scan QR Code Ini</h4>
                                        
                                        <div class="inline-block p-6 bg-white border border-gray-100 rounded-3xl shadow-sm mb-6 mt-4">
                                            <img :src="qrCodeImage" alt="QR Code" class="w-56 h-56 mx-auto" />
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <p class="text-xs font-medium text-blue-500 dark:text-blue-400 mb-6 flex items-center bg-blue-50 dark:bg-blue-900/30 px-3 py-1.5 rounded-full">
                                                <RefreshCw class="w-3.5 h-3.5 mr-1.5 animate-spin" /> Diperbarui otomatis
                                            </p>
                                            <Button variant="outline" @click="stopSession" :disabled="isLoading">Batalkan</Button>
                                        </div>
                                    </div>

                                    <div v-else-if="connectMethod === 'pairing' && pairingCode" class="animate-in zoom-in-95 duration-300">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kode Pairing Anda</h4>
                                        
                                        <div class="inline-block px-12 py-6 bg-gray-50 dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-3xl mb-8 mt-4">
                                            <span class="text-4xl md:text-5xl font-mono font-bold tracking-[0.25em] text-gray-900 dark:text-white ml-2">{{ pairingCode }}</span>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <p class="text-sm text-gray-500 dark:text-slate-400 mb-6">Segera masukkan kode ini sebelum kadaluarsa.</p>
                                            <Button variant="outline" @click="stopSession" :disabled="isLoading">Batalkan</Button>
                                        </div>
                                    </div>

                                    <div v-else class="py-20 animate-in fade-in duration-300">
                                        <Loader2 class="w-12 h-12 text-blue-500 dark:text-blue-400 animate-spin mx-auto mb-6" />
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Mempersiapkan Koneksi</h4>
                                        <Button variant="outline" class="mt-10" @click="stopSession" :disabled="isLoading">Batalkan</Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </div>
            </Tabs>
        </div>
    </AppLayout>
</template>
