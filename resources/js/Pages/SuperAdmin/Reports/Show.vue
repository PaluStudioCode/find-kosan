<script setup>
import { toast } from 'vue-sonner';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ChevronLeft, Info, CheckCircle2, MessageCircle, ShieldAlert } from 'lucide-vue-next';
import StatusBadge from '@/components/StatusBadge.vue';

import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';

const props = defineProps({
    report: Object,
});


const form = useForm({
    status: 'selesai',
    resolution_note: props.report.resolution_note || '',
    sanction: 'none',
});

const getWaLink = (phone, role) => {
    if (!phone) return '#';
    let formattedPhone = phone.replace(/^0/, '62');
    let message = role === 'reporter' 
        ? `Halo, kami dari Admin FindKosan menindaklanjuti laporan Anda terkait kos ${props.report.boarding_house?.name}. Bisa tolong kirimkan foto/bukti kendalanya?`
        : `Halo Bapak/Ibu Owner, kami dari Admin FindKosan mendapat laporan dari penyewa terkait kos ${props.report.boarding_house?.name}. Mohon klarifikasinya.`;
    return `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;
};

const isActionModalOpen = ref(false);

const submit = () => {
    form.put(route('superadmin.reports.update', props.report.id), {
        onSuccess: () => {
            isActionModalOpen.value = false;
            toast.success('Status laporan diperbarui.');
        }
    });
};

const confirmingReportDeletion = ref(false);
const isDeleting = ref(false);

const confirmReportDeletion = () => {
    confirmingReportDeletion.value = true;
};

const deleteReport = () => {
    isDeleting.value = true;
    router.delete(route('superadmin.reports.destroy', props.report.id), {
        onSuccess: () => {
            closeModal();
            toast.success('Laporan tidak valid berhasil dihapus.');
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};

const closeModal = () => {
    confirmingReportDeletion.value = false;
};

const categoryLabel = (cat) => {
    const labels = {
        'data_kos_tidak_valid': 'Data Kos Tidak Valid',
        'kontak_tidak_valid': 'Kontak Tidak Valid',
        'foto_tidak_sesuai': 'Foto Tidak Sesuai',
        'lainnya': 'Lainnya'
    };
    return labels[cat] || cat;
};
</script>

<template>
    <AppLayout>
        <Head :title="'Laporan #' + report.id" />

        <div class="max-w-4xl mx-auto space-y-4">
            <!-- Header Ringkas -->
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <Link :href="route('superadmin.reports.index')">
                        <Button variant="ghost" size="icon" class="rounded-full -ml-2 text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white"><ChevronLeft class="w-5 h-5" /></Button>
                    </Link>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Laporan #{{ report.id }}</h2>
                    <StatusBadge :status="report.status" />
                </div>
                <div class="text-sm text-gray-500 dark:text-slate-400 hidden sm:block">
                    {{ new Date(report.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                </div>
            </div>

            <!-- Single Clean Card -->
            <Card class="shadow-sm border-gray-200 dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <CardContent class="p-0 border-0">
                    <!-- Section 1: Masalah -->
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                        <div class="text-[11px] font-bold text-primary dark:text-blue-400 uppercase tracking-wider mb-2">{{ categoryLabel(report.category) }}</div>
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap text-sm leading-relaxed">{{ report.description }}</p>
                    </div>

                    <!-- Section 2: Pihak Terkait -->
                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x dark:divide-slate-800 border-b border-gray-100 dark:border-slate-800">
                        <!-- Pelapor -->
                        <div class="p-6 flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center font-bold text-blue-700 dark:text-blue-400 shrink-0">
                                    {{ report.reporter?.name ? report.reporter.name.charAt(0).toUpperCase() : '?' }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Pelapor</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ report.reporter?.name || 'Anonim' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ report.reporter?.whatsapp_number || report.reporter?.email || '-' }}</p>
                                </div>
                            </div>
                            <a :href="getWaLink(report.reporter?.whatsapp_number, 'reporter')" target="_blank" v-if="report.reporter?.whatsapp_number" class="w-full xl:w-auto">
                                <Button variant="outline" size="sm" class="w-full text-green-600 dark:text-green-400 border-green-200 dark:border-green-900/50 hover:bg-green-50 dark:hover:bg-green-900/30 shrink-0 bg-green-50/30 dark:bg-green-900/10"><MessageCircle class="w-4 h-4 sm:mr-2" /><span class="hidden sm:inline">WhatsApp</span></Button>
                            </a>
                        </div>
                        
                        <!-- Terlapor -->
                        <div class="p-6 flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-slate-800/50 transition-colors" v-if="report.boarding_house">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center font-bold text-orange-700 dark:text-orange-400 shrink-0">
                                    {{ report.boarding_house.admin?.name ? report.boarding_house.admin.name.charAt(0).toUpperCase() : '?' }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-0.5 truncate">Terlapor: <Link :href="route('public.kos.show', report.boarding_house.id)" target="_blank" class="text-primary dark:text-blue-400 hover:underline">{{ report.boarding_house.name }}</Link></p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ report.boarding_house.admin?.name || 'Tanpa Owner' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ report.boarding_house.admin?.whatsapp_number || '-' }}</p>
                                </div>
                            </div>
                            <a :href="getWaLink(report.boarding_house.admin?.whatsapp_number, 'admin')" target="_blank" v-if="report.boarding_house.admin?.whatsapp_number" class="w-full xl:w-auto">
                                <Button variant="outline" size="sm" class="w-full text-green-600 dark:text-green-400 border-green-200 dark:border-green-900/50 hover:bg-green-50 dark:hover:bg-green-900/30 shrink-0 bg-green-50/30 dark:bg-green-900/10"><MessageCircle class="w-4 h-4 sm:mr-2" /><span class="hidden sm:inline">WhatsApp</span></Button>
                            </a>
                        </div>
                    </div>

                    <!-- Section 3: History (Jika Selesai) -->
                    <div v-if="report.handled_by" class="px-6 py-3 bg-green-50/50 dark:bg-green-900/20 border-b border-gray-100 dark:border-slate-800 flex items-start gap-3">
                        <CheckCircle2 class="w-4 h-4 text-green-600 dark:text-green-400 mt-0.5 shrink-0" />
                        <div>
                            <p class="text-xs font-medium text-gray-800 dark:text-gray-300">Selesai oleh <span class="font-bold text-gray-900 dark:text-white">{{ report.handler?.name || 'Sistem' }}</span> pada {{ new Date(report.handled_at).toLocaleDateString('id-ID') }}</p>
                            <p v-if="report.resolution_note" class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ report.resolution_note }}</p>
                        </div>
                    </div>

                    <!-- Bottom Action Bar -->
                    <div v-if="report.status !== 'selesai'" class="p-4 bg-gray-50/50 dark:bg-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <Button type="button" @click="confirmReportDeletion" variant="ghost" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 text-sm">
                            Hapus Laporan (Tidak Valid)
                        </Button>
                        <Button type="button" @click="isActionModalOpen = true" class="w-full sm:w-auto px-8 shadow-sm">Tindak Lanjuti Laporan</Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog :open="confirmingReportDeletion" @update:open="confirmingReportDeletion = $event">
            <DialogContent class="sm:max-w-md dark:bg-slate-900 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="dark:text-white">Hapus Laporan Secara Permanen?</DialogTitle>
                </DialogHeader>
                <div class="py-2">
                    <p class="text-sm text-gray-600 dark:text-slate-400">
                        Apakah Anda yakin ingin menghapus laporan ini secara permanen dari sistem? Tindakan ini hanya boleh dilakukan untuk laporan yang terbukti palsu atau tidak valid agar tidak memenuhi *database*. Data yang dihapus tidak dapat dikembalikan.
                    </p>
                </div>
                <DialogFooter class="mt-4 gap-2 sm:gap-0">
                    <Button variant="outline" @click="closeModal" :disabled="isDeleting">Batal</Button>
                    <Button variant="destructive" @click="deleteReport" :disabled="isDeleting">
                        <span v-if="isDeleting">Menghapus...</span>
                        <span v-else>Ya, Hapus Laporan</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal Action / Tindak Lanjut -->
        <Dialog :open="isActionModalOpen" @update:open="isActionModalOpen = $event">
            <DialogContent class="sm:max-w-md dark:bg-slate-900 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="dark:text-white">Tindak Lanjuti Laporan</DialogTitle>
                </DialogHeader>
                
                <form @submit.prevent="submit" class="space-y-5 py-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-red-600 dark:text-red-400 flex items-center gap-2"><ShieldAlert class="w-4 h-4" /> Jatuhkan Sanksi</label>
                        <Select v-model="form.sanction">
                            <SelectTrigger class="bg-white dark:bg-slate-800 border-red-200 dark:border-red-900/50 focus:ring-red-500 dark:text-slate-200"><SelectValue /></SelectTrigger>
                            <SelectContent class="dark:bg-slate-800 dark:border-slate-700">
                                <SelectItem value="none" class="dark:text-slate-200 dark:focus:bg-slate-700">Tidak ada sanksi</SelectItem>
                                <SelectItem value="suspend_kos" class="text-orange-600 dark:text-orange-400 font-medium dark:focus:bg-slate-700">Suspend Kos</SelectItem>
                                <SelectItem value="ban_kos" class="text-red-600 dark:text-red-400 font-medium dark:focus:bg-slate-700">Banned Kos</SelectItem>
                                <SelectItem value="ban_owner" class="text-red-700 dark:text-red-500 font-bold dark:focus:bg-slate-700">Banned Owner</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-slate-300">Catatan Internal Admin <span class="text-red-500">*</span></label>
                        <Textarea v-model="form.resolution_note" rows="4" required class="resize-none bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500" :class="{'border-red-500 dark:border-red-500': form.errors.resolution_note}" placeholder="Wajib diisi. Tuliskan alasan keputusan atau arsip bukti..." />
                        <p v-if="form.errors.resolution_note" class="text-xs text-red-500 dark:text-red-400">{{ form.errors.resolution_note }}</p>
                    </div>

                    <DialogFooter class="mt-4 gap-2 sm:gap-0">
                        <Button type="button" variant="outline" @click="isActionModalOpen = false" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan Keputusan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
