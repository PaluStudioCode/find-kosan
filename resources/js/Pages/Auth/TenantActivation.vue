<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Eye, EyeOff, Loader2, Info } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route('tenant.activation.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Aktivasi Akun" />

        <Card>
            <CardHeader class="space-y-1 text-center">
                <CardTitle class="text-2xl font-bold">Aktivasi Akun Penyewa</CardTitle>
                <CardDescription>
                    Buat password baru untuk mengaktifkan akun penyewa Anda
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="mb-6 flex items-start space-x-3 rounded-md bg-blue-50 p-3 text-sm text-blue-800 border border-blue-200">
                    <Info class="h-5 w-5 shrink-0 text-blue-500 mt-0.5" />
                    <p>Setelah diaktifkan, Anda dapat login menggunakan email Anda dan password baru ini.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="password">Password Baru</Label>
                        <div class="relative">
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                autofocus
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

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Aktifkan Akun
                    </Button>
                </form>
            </CardContent>
        </Card>
    </GuestLayout>
</template>
