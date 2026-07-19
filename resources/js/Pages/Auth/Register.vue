<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Info, Eye, EyeOff, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    whatsapp_number: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <Card class="border-0 shadow-lg sm:rounded-xl overflow-hidden">
            <CardHeader class="space-y-2 text-center pb-6">
                <CardTitle class="text-2xl font-bold tracking-tight">Daftar Pemilik Kos</CardTitle>
                <CardDescription class="text-base">
                    Buat akun untuk mulai mengelola kos Anda
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="mb-6 flex items-start gap-3 rounded-lg bg-blue-50/50 p-4 text-sm text-blue-900 border border-blue-100 shadow-sm">
                    <Info class="h-5 w-5 shrink-0 text-blue-500 mt-0.5" />
                    <p class="leading-relaxed">Kos tidak akan tampil di publik sebelum data kos dan dokumen legalitas wajib diverifikasi oleh admin.</p>
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

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Daftar
                    </Button>
                </form>
            </CardContent>
            <CardFooter class="flex justify-center border-t p-4">
                <p class="text-sm text-muted-foreground">
                    Sudah punya akun?
                    <Link :href="route('login')" class="font-semibold text-primary hover:underline">
                        Masuk
                    </Link>
                </p>
            </CardFooter>
        </Card>
        
        <div class="mt-4 text-center">
            <Link href="/" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                &larr; Kembali ke pencarian kos
            </Link>
        </div>
    </GuestLayout>
</template>
