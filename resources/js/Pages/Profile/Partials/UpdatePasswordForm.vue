<script setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Perbarui Password</CardTitle>
            <CardDescription>
                Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.
            </CardDescription>
        </CardHeader>

        <form @submit.prevent="updatePassword">
            <CardContent class="space-y-6">
                <div class="space-y-2">
                    <Label for="current_password">Password Saat Ini</Label>
                    <Input
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        autocomplete="current-password"
                        :class="{ 'border-destructive': form.errors.current_password }"
                    />
                    <p v-if="form.errors.current_password" class="text-sm text-destructive">{{ form.errors.current_password }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="password">Password Baru</Label>
                    <Input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        :class="{ 'border-destructive': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="password_confirmation">Konfirmasi Password</Label>
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        :class="{ 'border-destructive': form.errors.password_confirmation }"
                    />
                    <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">{{ form.errors.password_confirmation }}</p>
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
