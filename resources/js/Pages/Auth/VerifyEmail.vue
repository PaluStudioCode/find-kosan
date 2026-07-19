<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import { Loader2 } from 'lucide-vue-next';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <Card>
            <CardHeader class="space-y-1 text-center">
                <CardTitle class="text-2xl font-bold">Verifikasi Email</CardTitle>
                <CardDescription>
                    Terima kasih telah mendaftar! Sebelum memulai, mohon verifikasi alamat email Anda dengan mengeklik tautan yang baru saja kami kirimkan ke email Anda.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="verificationLinkSent" class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700 border border-green-200">
                    Tautan verifikasi baru telah dikirimkan ke alamat email yang Anda berikan saat pendaftaran.
                </div>

                <p class="mb-6 text-sm text-muted-foreground text-center">
                    Jika Anda tidak menerima email tersebut, kami akan dengan senang hati mengirimkannya kembali.
                </p>

                <form @submit.prevent="submit" class="space-y-4">
                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Kirim Ulang Email Verifikasi
                    </Button>
                </form>
            </CardContent>
            <CardFooter class="flex justify-center border-t p-4">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-medium text-muted-foreground hover:text-primary transition-colors"
                >
                    Log Out
                </Link>
            </CardFooter>
        </Card>
    </GuestLayout>
</template>
