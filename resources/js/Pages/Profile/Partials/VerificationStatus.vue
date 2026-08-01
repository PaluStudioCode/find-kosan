<script setup>
import { ref, computed } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { CheckCircle2, AlertCircle } from 'lucide-vue-next';
import OtpVerificationModal from '@/Components/OtpVerificationModal.vue';
import { toast } from 'vue-sonner';

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);

const isEmailVerified = computed(() => user.value.email_verified_at !== null);
const isWaVerified = computed(() => user.value.whatsapp_number !== null && user.value.whatsapp_number !== '');

const showOtpModal = ref(false);
const otpType = ref('email'); // 'email' or 'wa'
const targetString = ref('');

const waForm = useForm({
    whatsapp_number: user.value.whatsapp_number || '',
});

const sendEmailOtp = () => {
    useForm({}).post(route('profile.email.send-otp'), {
        preserveScroll: true,
        onSuccess: () => {
            if (flash.value.email_otp_sent) {
                otpType.value = 'email';
                targetString.value = user.value.email;
                showOtpModal.value = true;
            }
        },
        onError: (errors) => {
            if (errors.email) toast.error(errors.email);
        }
    });
};

const sendWaOtp = () => {
    if (!waForm.whatsapp_number) {
        toast.error('Masukkan nomor WhatsApp terlebih dahulu');
        return;
    }
    waForm.post(route('profile.wa.send-otp'), {
        preserveScroll: true,
        onSuccess: () => {
            if (flash.value.wa_otp_sent) {
                otpType.value = 'wa';
                targetString.value = waForm.whatsapp_number;
                showOtpModal.value = true;
            }
        },
        onError: (errors) => {
            if (errors.whatsapp_number) toast.error(errors.whatsapp_number);
        }
    });
};

const handleOtpSubmit = (otpCode) => {
    const form = useForm({ otp: otpCode });
    const verifyRoute = otpType.value === 'email' ? route('profile.email.verify-otp') : route('profile.wa.verify-otp');
    
    form.post(verifyRoute, {
        preserveScroll: true,
        onSuccess: () => {
            showOtpModal.value = false;
        },
        onError: (errors) => {
            if (errors.otp) toast.error(errors.otp);
        }
    });
};
</script>

<template>
    <section class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm mb-6">
        <header class="mb-6">
            <h2 class="text-lg font-medium text-gray-900">Status Verifikasi Akun</h2>
            <p class="mt-1 text-sm text-gray-600">
                Verifikasi Email dan WhatsApp diperlukan untuk mengakses fitur utama aplikasi seperti pemesanan kamar atau publikasi properti kos.
            </p>
        </header>

        <div class="space-y-6">
            <!-- Email Verification -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 border rounded-lg" :class="isEmailVerified ? 'border-green-200 bg-green-50/50' : 'border-amber-200 bg-amber-50/50'">
                <div>
                    <h3 class="font-medium text-gray-900 flex items-center gap-2">
                        Verifikasi Email
                        <CheckCircle2 v-if="isEmailVerified" class="w-4 h-4 text-green-600" />
                        <AlertCircle v-else class="w-4 h-4 text-amber-500" />
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">{{ user.email }}</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <span v-if="isEmailVerified" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Terverifikasi
                    </span>
                    <Button v-else @click="sendEmailOtp" variant="outline" size="sm" class="border-amber-300 text-amber-700 hover:bg-amber-100">
                        Kirim Kode Verifikasi
                    </Button>
                </div>
            </div>

            <!-- WhatsApp Verification -->
            <div class="flex flex-col p-4 border rounded-lg" :class="isWaVerified ? 'border-green-200 bg-green-50/50' : 'border-amber-200 bg-amber-50/50'">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4">
                    <div>
                        <h3 class="font-medium text-gray-900 flex items-center gap-2">
                            Verifikasi WhatsApp
                            <CheckCircle2 v-if="isWaVerified" class="w-4 h-4 text-green-600" />
                            <AlertCircle v-else class="w-4 h-4 text-amber-500" />
                        </h3>
                        <p v-if="isWaVerified" class="text-sm text-gray-600 mt-1">{{ user.whatsapp_number }}</p>
                        <p v-else class="text-sm text-amber-600 mt-1">Anda belum mendaftarkan nomor WhatsApp.</p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <span v-if="isWaVerified" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Terverifikasi
                        </span>
                    </div>
                </div>

                <div v-if="!isWaVerified" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <Label for="wa_number" class="sr-only">Nomor WhatsApp</Label>
                        <Input 
                            id="wa_number" 
                            v-model="waForm.whatsapp_number" 
                            type="text" 
                            placeholder="Contoh: 08123456789" 
                            :disabled="waForm.processing"
                        />
                        <p v-if="waForm.errors.whatsapp_number" class="text-sm text-red-500 mt-1">{{ waForm.errors.whatsapp_number }}</p>
                    </div>
                    <Button @click="sendWaOtp" :disabled="waForm.processing" variant="outline" class="border-amber-300 text-amber-700 hover:bg-amber-100 whitespace-nowrap">
                        Verifikasi WhatsApp
                    </Button>
                </div>
            </div>
        </div>

        <OtpVerificationModal 
            :is-open="showOtpModal"
            @update:is-open="showOtpModal = $event"
            :type="otpType"
            :target="targetString"
            @submit="handleOtpSubmit"
            @resend="otpType === 'email' ? sendEmailOtp() : sendWaOtp()"
        />
    </section>
</template>
