<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
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

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar Akun" />

        <!-- Step 1: Role Selection -->
        <div v-if="!selectedRole" class="w-full max-w-lg mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Buat Akun Baru</h1>
                <p class="text-gray-500 mt-2">Pilih jenis akun yang sesuai dengan kebutuhan Anda</p>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <!-- Penyewa Card -->
                <Card 
                    class="cursor-pointer border-2 transition-all duration-200 hover:border-primary hover:shadow-lg group"
                    @click="selectRole('penyewa')"
                >
                    <CardContent class="p-6 flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <Search class="w-7 h-7" />
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
                    @click="selectRole('pemilik_kos')"
                >
                    <CardContent class="p-6 flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center shrink-0 group-hover:bg-green-600 group-hover:text-white transition-colors">
                            <Home class="w-7 h-7" />
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
        <Card v-else class="border-0 shadow-lg sm:rounded-xl overflow-hidden">
            <CardHeader class="space-y-2 text-center pb-6">
                <CardTitle class="text-2xl font-bold tracking-tight">
                    {{ form.role === 'penyewa' ? 'Daftar Sebagai Penyewa' : 'Daftar Sebagai Pemilik Kos' }}
                </CardTitle>
                <CardDescription class="text-base">
                    {{ form.role === 'penyewa' 
                        ? 'Buat akun untuk mulai mencari dan menyewa kos' 
                        : 'Buat akun untuk mulai mengelola kos Anda' }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Info Box -->
                <div v-if="form.role === 'pemilik_kos'" class="mb-6 flex items-start gap-3 rounded-lg bg-blue-50/50 p-4 text-sm text-blue-900 border border-blue-100 shadow-sm">
                    <Info class="h-5 w-5 shrink-0 text-blue-500 mt-0.5" />
                    <p class="leading-relaxed">Kos tidak akan tampil di publik sebelum data kos dan dokumen legalitas wajib diverifikasi oleh admin.</p>
                </div>
                <div v-else class="mb-6 flex items-start gap-3 rounded-lg bg-green-50/50 p-4 text-sm text-green-900 border border-green-100 shadow-sm">
                    <Info class="h-5 w-5 shrink-0 text-green-500 mt-0.5" />
                    <p class="leading-relaxed">Setelah mendaftar, Anda dapat langsung mencari kos dan mengajukan penyewaan kamar.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
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

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
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
                </form>
            </CardContent>
            <CardFooter class="flex flex-col gap-3 border-t p-4">
                <Button variant="ghost" class="w-full text-muted-foreground" @click="goBack">
                    <ArrowLeft class="w-4 h-4 mr-2" /> Pilih jenis akun lain
                </Button>
                <p class="text-sm text-muted-foreground text-center">
                    Sudah punya akun?
                    <Link :href="route('login')" class="font-semibold text-primary hover:underline">Masuk</Link>
                </p>
            </CardFooter>
        </Card>
        
        <div v-if="selectedRole" class="mt-4 text-center">
            <Link href="/" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                &larr; Kembali ke pencarian kos
            </Link>
        </div>
    </GuestLayout>
</template>
