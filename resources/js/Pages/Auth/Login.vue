<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Eye, EyeOff, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <Card class="border-0 shadow-none bg-transparent">
            <CardHeader class="space-y-1 text-center">
                <CardTitle class="text-2xl font-bold">Masuk</CardTitle>
                <CardDescription>
                    Masukkan email dan password Anda untuk masuk ke akun
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="status" class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700 border border-green-200">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            :class="{ 'border-destructive': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label for="password">Password</Label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-medium text-primary hover:underline"
                            >
                                Lupa password?
                            </Link>
                        </div>
                        <div class="relative">
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
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

                    <div class="flex items-center space-x-2">
                        <Checkbox id="remember" :checked="form.remember" @update:checked="v => form.remember = v" />
                        <label
                            for="remember"
                            class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                        >
                            Ingat saya
                        </label>
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Log in
                    </Button>

                    <div class="relative my-4">
                        <div class="absolute inset-0 flex items-center">
                            <span class="w-full border-t" />
                        </div>
                        <div class="relative flex justify-center text-xs uppercase">
                            <span class="bg-background px-2 text-muted-foreground">
                                Atau
                            </span>
                        </div>
                    </div>

                    <a :href="route('google.login')" class="flex items-center justify-center w-full rounded-md border border-input bg-background px-8 py-2.5 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                        <svg class="mr-2 h-4 w-4" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="google" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512">
                            <path fill="currentColor" d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"></path>
                        </svg>
                        Lanjutkan dengan Google
                    </a>
                </form>
            </CardContent>
            <CardFooter class="flex justify-center pt-6">
                <p class="text-sm text-muted-foreground">
                    Belum punya akun?
                    <Link :href="route('register')" class="font-semibold text-primary hover:underline">
                        Daftar sekarang
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
