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

        <Card>
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
                </form>
            </CardContent>
            <CardFooter class="flex justify-center border-t p-4">
                <p class="text-sm text-muted-foreground">
                    Belum punya akun pemilik kos?
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
