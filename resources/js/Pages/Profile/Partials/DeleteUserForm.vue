<script setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription as ShadcnDialogDesc, DialogFooter } from '@/components/ui/dialog';
import { AlertTriangle, Loader2 } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <Card class="border-destructive">
        <CardHeader>
            <CardTitle class="text-destructive">Hapus Akun</CardTitle>
            <CardDescription>
                Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen.
                Sebelum menghapus akun Anda, harap unduh data atau informasi apa pun yang ingin Anda simpan.
            </CardDescription>
        </CardHeader>

        <CardContent>
            <Button variant="destructive" @click="confirmUserDeletion">Hapus Akun</Button>
        </CardContent>

        <Dialog :open="confirmingUserDeletion" @update:open="val => { if(!val) closeModal(); }">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <div class="flex items-center gap-4 mb-2 text-destructive">
                        <div class="p-3 bg-destructive/10 rounded-full shrink-0">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <DialogTitle>Apakah Anda yakin ingin menghapus akun?</DialogTitle>
                    </div>
                </DialogHeader>

                <ShadcnDialogDesc class="text-sm">
                    Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen.
                    Silakan masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun secara permanen.
                </ShadcnDialogDesc>

                <div class="mt-4 space-y-2">
                    <Label for="password" class="sr-only">Password</Label>
                    <Input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                        :class="{ 'border-destructive': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                </div>

                <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                    <Button variant="outline" @click="closeModal">Batal</Button>
                    <Button variant="destructive" :disabled="form.processing" @click="deleteUser">
                        <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                        Hapus Akun
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </Card>
</template>
