<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Info, Eye, EyeOff, Loader2, Home, Search, ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

const selectedRole = ref(null);

const form = useForm({
    name: '',
    email: '',
    whatsapp_number: '',
    password: '',
    password_confirmation: '',
    role: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const selectRole = (role) => {
    selectedRole.value = role;
    form.role = role;
};

const goBack = () => {
    selectedRole.value = null;
    form.role = '';
    form.clearErrors();
};

const showOtpForm = ref(false);

const otpForm = useForm({
    whatsapp_number: '',
    otp: '',
});

const submit = () => {
    form.post(route('register'), {
        onSuccess: (page) => {
            if (page.props.flash?.otp_sent) {
                showOtpForm.value = true;
                otpForm.whatsapp_number = page.props.flash.whatsapp_number;
            }
            form.reset('password', 'password_confirmation');
        },
        onError: () => form.reset('password', 'password_confirmation'),
    });
};

const verifyOtpSubmit = () => {
    otpForm.post(route('register.verify-otp'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar Akun" />

        <!-- Step 1: Role Selection -->
        <div v-if="!selectedRole" class="w-full max-w-lg mx-auto">
            <div class="text-center mb-4">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Buat Akun Baru</h1>
                <p class="text-gray-500 mt-1">Pilih jenis akun yang sesuai dengan kebutuhan Anda</p>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <!-- Penyewa Card -->
                <Card 
                    class="cursor-pointer border-2 transition-all duration-200 hover:border-primary hover:shadow-lg group"
                    @click="selectRole('user')"
                >
                    <CardContent class="p-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <Search class="w-5 h-5" />
                        </div>
                        <div class="flex-grow">
                            <h3 class="font-bold text-lg text-gray-900">Saya Mencari Kos</h3>
                            <p class="text-sm text-gray-500 mt-1">Cari kos, ajukan sewa, dan kelola pembayaran sewa Anda secara online.</p>
                        </div>
                        <div class="text-gray-300 group-hover:text-primary transition-colors shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pemilik Kos Card -->
                <Card 
                    class="cursor-pointer border-2 transition-all duration-200 hover:border-primary hover:shadow-lg group"
                    @click="selectRole('admin')"
                >
                    <CardContent class="p-4 flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-green-100 text-green-600 flex items-center justify-center shrink-0 group-hover:bg-green-600 group-hover:text-white transition-colors">
                            <Home class="w-5 h-5" />
                        </div>
                        <div class="flex-grow">
                            <h3 class="font-bold text-lg text-gray-900">Saya Pemilik Kos</h3>
                            <p class="text-sm text-gray-500 mt-1">Kelola properti kos, terima penyewa, dan atur tagihan pembayaran.</p>
                        </div>
                        <div class="text-gray-300 group-hover:text-primary transition-colors shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-muted-foreground">
                    Sudah punya akun?
                    <Link :href="route('login')" class="font-semibold text-primary hover:underline">Masuk</Link>
                </p>
            </div>
        </div>

        <!-- Step 2: Registration Form -->
        <Card v-else-if="!showOtpForm" class="border-0 shadow-none bg-transparent">
            <CardHeader class="space-y-1 text-center pb-2">
                <CardTitle class="text-2xl font-bold tracking-tight">
                    {{ form.role === 'user' ? 'Daftar Sebagai Penyewa' : 'Daftar Sebagai Pemilik Kos' }}
                </CardTitle>
                <CardDescription class="text-base">
                    {{ form.role === 'user' 
                        ? 'Buat akun untuk mulai mencari dan menyewa kos' 
                        : 'Buat akun untuk mulai mengelola kos Anda' }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Info Box -->
                <div v-if="form.role === 'admin'" class="mb-3 flex items-start gap-3 rounded-lg bg-blue-50/50 p-3 text-sm text-blue-900 border border-blue-100 shadow-sm">
                    <Info class="h-5 w-5 shrink-0 text-blue-500 mt-0.5" />
                    <p class="leading-relaxed">Kos tidak akan tampil di publik sebelum data kos dan dokumen legalitas wajib diverifikasi oleh admin.</p>
                </div>
                <div v-else class="mb-3 flex items-start gap-3 rounded-lg bg-green-50/50 p-3 text-sm text-green-900 border border-green-100 shadow-sm">
                    <Info class="h-5 w-5 shrink-0 text-green-500 mt-0.5" />
                    <p class="leading-relaxed">Setelah mendaftar, Anda dapat langsung mencari kos dan mengajukan penyewaan kamar.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-3">
                    <div class="space-y-2">
                        <Label for="name">Nama Lengkap</Label>
                        <Input
                            id="name"
                            type="text"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            :class="{ 'border-destructive': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            :class="{ 'border-destructive': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="whatsapp_number">Nomor WhatsApp</Label>
                        <Input
                            id="whatsapp_number"
                            type="text"
                            v-model="form.whatsapp_number"
                            required
                            autocomplete="tel"
                            placeholder="08123456789"
                            :class="{ 'border-destructive': form.errors.whatsapp_number }"
                        />
                        <p class="text-xs text-muted-foreground">Format: dimulai dengan 08 atau +62</p>
                        <p v-if="form.errors.whatsapp_number" class="text-sm text-destructive">{{ form.errors.whatsapp_number }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="password">Password</Label>
                            <div class="relative">
                                <Input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    required
                                    autocomplete="new-password"
                                    :class="{ 'border-destructive': form.errors.password }"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground focus:outline-none"
                                >
                                    <Eye v-if="!showPassword" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">Konfirmasi Password</Label>
                            <div class="relative">
                                <Input
                                    id="password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    v-model="form.password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    :class="{ 'border-destructive': form.errors.password_confirmation }"
                                />
                                <button
                                    type="button"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground focus:outline-none"
                                >
                                    <Eye v-if="!showPasswordConfirmation" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">{{ form.errors.password_confirmation }}</p>
                        </div>
                    </div>

                    <p v-if="form.errors.role" class="text-sm text-destructive">{{ form.errors.role }}</p>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Daftar
                    </Button>

                    <div class="relative my-3">
                        <div class="absolute inset-0 flex items-center">
                            <span class="w-full border-t" />
                        </div>
                        <div class="relative flex justify-center text-xs uppercase">
                            <span class="bg-background px-2 text-muted-foreground">
                                Atau
                            </span>
                        </div>
                    </div>

                    <a :href="route('google.login', { role: form.role })" class="flex items-center justify-center w-full rounded-md border border-input bg-background px-8 py-2.5 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                        <svg class="mr-2 h-4 w-4" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="google" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512">
                            <path fill="currentColor" d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"></path>
                        </svg>
                        Lanjutkan dengan Google
                    </a>
                </form>
            </CardContent>
            <CardFooter class="flex flex-col gap-2 pt-3">
                <Button variant="ghost" class="w-full text-muted-foreground" @click="goBack">
                    <ArrowLeft class="w-4 h-4 mr-2" /> Pilih jenis akun lain
                </Button>
                <p class="text-sm text-muted-foreground text-center">
                    Sudah punya akun?
                    <Link :href="route('login')" class="font-semibold text-primary hover:underline">Masuk</Link>
                </p>
            </CardFooter>
        </Card>

        <!-- Step 3: OTP Form -->
        <Card v-else class="border-0 shadow-none bg-transparent">
            <CardHeader class="space-y-1 text-center pb-2">
                <CardTitle class="text-2xl font-bold tracking-tight">Verifikasi OTP</CardTitle>
                <CardDescription class="text-base">
                    Kode OTP 6 digit telah dikirimkan ke nomor WhatsApp Anda.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="verifyOtpSubmit" class="space-y-4 mt-4">
                    <div class="space-y-2">
                        <Label for="otp" class="text-center block">Kode OTP</Label>
                        <Input
                            id="otp"
                            type="text"
                            v-model="otpForm.otp"
                            required
                            autofocus
                            maxlength="6"
                            placeholder="123456"
                            class="text-center tracking-widest text-lg"
                            :class="{ 'border-destructive': otpForm.errors.otp }"
                        />
                        <p v-if="otpForm.errors.otp" class="text-sm text-destructive text-center">{{ otpForm.errors.otp }}</p>
                    </div>

                    <Button type="submit" class="w-full mt-6" :disabled="otpForm.processing">
                        <Loader2 v-if="otpForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Verifikasi OTP
                    </Button>
                </form>
            </CardContent>
            <CardFooter class="flex flex-col gap-2 pt-3">
                <Button variant="ghost" class="w-full text-muted-foreground" @click="showOtpForm = false">
                    <ArrowLeft class="w-4 h-4 mr-2" /> Kembali Edit Data
                </Button>
            </CardFooter>
        </Card>
        
        <div v-if="selectedRole" class="mt-2 text-center">
            <Link href="/" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                &larr; Kembali ke pencarian kos
            </Link>
        </div>
    </GuestLayout>
</template>
