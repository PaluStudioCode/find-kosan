<script setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Informasi Profil</CardTitle>
            <CardDescription>
                Perbarui informasi profil akun dan alamat email Anda.
            </CardDescription>
        </CardHeader>

        <form @submit.prevent="form.patch(route('profile.update'))">
            <CardContent class="space-y-6">
                <div class="space-y-2">
                    <Label for="name">Nama</Label>
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

                <div v-if="mustVerifyEmail && user.email_verified_at === null">
                    <p class="text-sm text-muted-foreground">
                        Alamat email Anda belum diverifikasi.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="font-medium text-primary hover:underline"
                        >
                            Klik di sini untuk mengirim ulang email verifikasi.
                        </Link>
                    </p>

                    <div
                        v-show="status === 'verification-link-sent'"
                        class="mt-2 text-sm font-medium text-green-600"
                    >
                        Tautan verifikasi baru telah dikirim ke alamat email Anda.
                    </div>
                </div>
            </CardContent>

            <CardFooter class="flex items-center gap-4">
                <Button type="submit" :disabled="form.processing">
                    <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                    Simpan
                </Button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Tersimpan.</p>
                </Transition>
            </CardFooter>
        </form>
    </Card>
</template>
