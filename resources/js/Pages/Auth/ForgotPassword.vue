<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Loader2 } from 'lucide-vue-next';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password" />

        <Card>
            <CardHeader class="space-y-1 text-center">
                <CardTitle class="text-2xl font-bold">Lupa Password</CardTitle>
                <CardDescription>
                    Masukkan email Anda dan kami akan mengirimkan tautan untuk mereset password.
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

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Kirim Tautan Reset Password
                    </Button>
                </form>
            </CardContent>
        </Card>
        
        <div class="mt-4 text-center">
            <Link :href="route('login')" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                &larr; Kembali ke halaman login
            </Link>
        </div>
    </GuestLayout>
</template>
