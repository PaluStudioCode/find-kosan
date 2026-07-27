<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { useToast } from '@/components/ui/toast/use-toast';
import { CheckCircle, Clock, XCircle, AlertTriangle, ShieldCheck } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';

const props = defineProps({
    kos: Object
});

const { toast } = useToast();

const confirmingVerification = ref(false);

const confirmVerification = () => {
    confirmingVerification.value = true;
};

const closeVerificationModal = () => {
    confirmingVerification.value = false;
};

const requestVerification = () => {
    router.post(route('owner.kos.verify', props.kos.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Notifikasi sukses ditangani oleh layout global (flash.success)
            closeVerificationModal();
        },
        onError: (err) => {
            toast({ title: 'Gagal', description: err.error || 'Terjadi kesalahan.', variant: 'destructive' });
            closeVerificationModal();
        }
    });
};

const hasLegalDocs = props.kos.legal_documents && props.kos.legal_documents.length > 0;
const hasPhotos = props.kos.photos && props.kos.photos.length > 0;
const canSubmit = hasLegalDocs && hasPhotos && ['draft', 'ditolak', 'nonaktif'].includes(props.kos.status);
</script>

<template>
    <div class="max-w-3xl mx-auto space-y-6 py-4">
        
        <div class="text-center space-y-3 mb-6">
            <h3 class="text-xl font-bold">Status Publikasi Properti</h3>
            <div class="flex justify-center items-center gap-2 text-base">
                Status Saat Ini: <StatusBadge :status="kos.status" class="px-2 py-0.5" />
            </div>
            <p v-if="kos.status === 'dipublikasikan'" class="text-green-600 dark:text-green-400 font-medium text-sm">Properti Anda sedang aktif dan dapat dilihat oleh publik.</p>
        </div>

        <!-- BANNER SANKSI COMPACT -->
        <div v-if="kos.status === 'nonaktif' && kos.reports && kos.reports.length > 0" class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 rounded-lg p-4 flex items-start gap-3">
            <ShieldAlert class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" />
            <div>
                <h4 class="font-bold text-red-800 dark:text-red-400 text-sm">Ditangguhkan oleh Admin</h4>
                <p class="text-xs text-red-700 dark:text-red-300 mt-1">Kos diturunkan dari publik akibat pelanggaran/laporan. Silakan perbaiki data dan ajukan verifikasi ulang.</p>
                <div class="mt-2 bg-white/60 dark:bg-slate-900/60 p-2.5 rounded text-xs text-red-900 dark:text-red-200 border border-red-100 dark:border-red-900/50">
                    <span class="font-bold">Catatan Admin:</span> {{ kos.reports[0].resolution_note }}
                </div>
            </div>
        </div>

        <div v-if="kos.status === 'ditolak'" class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 rounded-lg p-6 flex items-start gap-4">
            <XCircle class="w-8 h-8 text-red-500 dark:text-red-400 shrink-0" />
            <div>
                <h4 class="text-lg font-bold text-red-800 dark:text-red-400">Verifikasi Ditolak</h4>
                <p class="text-red-700 dark:text-red-300 mt-2">{{ kos.verification_note || 'Tidak ada catatan penolakan.' }}</p>
                <p class="text-sm text-red-600 dark:text-red-400 mt-4">Silakan perbaiki data properti atau lengkapi dokumen legalitas yang diminta, kemudian ajukan verifikasi kembali.</p>
            </div>
        </div>

        <div v-if="kos.pending_revisions" class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 rounded-lg p-6 flex items-start gap-4">
            <AlertTriangle class="w-8 h-8 text-blue-500 dark:text-blue-400 shrink-0" />
            <div>
                <h4 class="text-lg font-bold text-blue-800 dark:text-blue-400">Revisi Menunggu Persetujuan</h4>
                <p class="text-blue-700 dark:text-blue-300 mt-2">Anda telah melakukan perubahan data pada properti yang sudah dipublikasikan. Perubahan tersebut berstatus sebagai <strong>draft revisi</strong> (Shadow Revision) dan menunggu persetujuan Super Admin.</p>
                <p class="text-sm text-blue-600 dark:text-blue-400 mt-2">Data publik saat ini masih menggunakan data valid terakhir Anda hingga revisi disetujui.</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border dark:border-slate-800 rounded-lg p-6 shadow-sm">
            <h4 class="font-semibold text-lg border-b dark:border-slate-800 pb-2 mb-4">Syarat Verifikasi & Publikasi</h4>
            
            <ul class="space-y-4">
                <li class="flex items-start gap-3">
                    <CheckCircle class="w-5 h-5 text-green-500 dark:text-green-400 shrink-0" />
                    <div>
                        <p class="font-medium">Data Utama Kos Lengkap</p>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Nama, deskripsi, fasilitas, alamat, dan titik peta terisi dengan benar.</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <component :is="hasPhotos ? CheckCircle : Clock" class="w-5 h-5 shrink-0" :class="hasPhotos ? 'text-green-500 dark:text-green-400' : 'text-gray-400 dark:text-slate-500'" />
                    <div>
                        <p class="font-medium" :class="hasPhotos ? 'text-gray-900 dark:text-slate-200' : 'text-gray-500 dark:text-slate-400'">Minimal 1 Foto Kos</p>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Foto akan ditampilkan di pencarian publik.</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <component :is="hasLegalDocs ? CheckCircle : Clock" class="w-5 h-5 shrink-0" :class="hasLegalDocs ? 'text-green-500 dark:text-green-400' : 'text-gray-400 dark:text-slate-500'" />
                    <div>
                        <p class="font-medium" :class="hasLegalDocs ? 'text-gray-900 dark:text-slate-200' : 'text-gray-500 dark:text-slate-400'">Dokumen Legalitas</p>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Minimal 1 dokumen seperti KTP / SHM (bersifat rahasia).</p>
                    </div>
                </li>
            </ul>
        </div>

        <div v-if="['draft', 'ditolak', 'nonaktif'].includes(kos.status)" class="text-center pt-4">
            <Button size="lg" class="w-full md:w-auto px-12" @click="confirmVerification" :disabled="!canSubmit">
                <ShieldCheck class="w-5 h-5 mr-2" />
                Ajukan Verifikasi Sekarang
            </Button>
            <p v-if="!canSubmit" class="text-xs text-red-500 dark:text-red-400 mt-2">Anda harus mengunggah minimal 1 foto kos dan 1 dokumen legalitas untuk dapat mengajukan verifikasi.</p>
        </div>
        
        <div v-else-if="kos.status === 'menunggu_verifikasi'" class="text-center p-6 bg-gray-50 dark:bg-slate-800/50 border dark:border-slate-800 rounded-lg">
            <Clock class="w-12 h-12 text-orange-400 mx-auto mb-4" />
            <h4 class="text-lg font-bold text-gray-900 dark:text-slate-200">Sedang Ditinjau</h4>
            <p class="text-gray-600 dark:text-slate-400 mt-2">Pengajuan verifikasi Anda telah kami terima dan sedang ditinjau oleh tim Super Admin. Proses ini biasanya memakan waktu 1x24 jam kerja.</p>
        </div>
    </div>

    <!-- Modal Dialog Konfirmasi Verifikasi -->
    <Dialog :open="confirmingVerification" @update:open="val => { if(!val) closeVerificationModal(); }">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <div class="flex items-center gap-4 mb-2 text-primary">
                    <div class="p-3 bg-primary/10 rounded-full shrink-0">
                        <ShieldCheck class="w-6 h-6" />
                    </div>
                    <DialogTitle>Ajukan Verifikasi?</DialogTitle>
                </div>
            </DialogHeader>
            
            <DialogDescription class="text-sm">
                Apakah Anda yakin data kos ini sudah lengkap dan ingin mengajukan verifikasi ke tim Super Admin?
            </DialogDescription>

            <DialogFooter class="mt-6 flex flex-col sm:flex-row justify-end gap-2">
                <Button variant="outline" @click="closeVerificationModal" class="w-full sm:w-auto">Batal</Button>
                <Button @click="requestVerification" class="w-full sm:w-auto">Ya, Ajukan Verifikasi</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
