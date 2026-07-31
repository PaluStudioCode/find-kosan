<script setup>
import { toast } from 'vue-sonner';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { ChevronLeft, ChevronRight, Download, CheckCircle, FileText, Eye, MapPin, Map, Check, X, AlertTriangle, Image as ImageIcon } from 'lucide-vue-next';
import MapPicker from '@/components/MapPicker.vue';

const props = defineProps({
    kos: Object
});

const rejectForm = useForm({ note: '' });
const showRejectForm = ref(false);

const confirmingApproval = ref(false);

const approve = () => {
    router.post(route('superadmin.verifications.approve', props.kos.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            confirmingApproval.value = false;
            toast.success('Kos disetujui dan dipublikasikan.');
        },
    });
};

const reject = () => {
    rejectForm.post(route('superadmin.verifications.reject', props.kos.id), {
        onSuccess: () => {
            toast.success('Kos ditolak.');
            showRejectForm.value = false;
        },
    });
};

// Photo Slider State
const showPhotoSlider = ref(false);
const currentPhotoIndex = ref(0);

const openPhotoSlider = (index) => {
    currentPhotoIndex.value = index;
    showPhotoSlider.value = true;
};

const nextPhoto = () => {
    if (currentPhotoIndex.value < props.kos.photos.length - 1) {
        currentPhotoIndex.value++;
    } else {
        currentPhotoIndex.value = 0;
    }
};

const prevPhoto = () => {
    if (currentPhotoIndex.value > 0) {
        currentPhotoIndex.value--;
    } else {
        currentPhotoIndex.value = props.kos.photos.length - 1;
    }
};

const photoCategories = {
    'bangunan_depan': 'Tampak Depan',
    'dalam_kamar': 'Dalam Kamar',
    'kamar_mandi': 'Kamar Mandi',
    'fasilitas_umum': 'Fasilitas Umum',
    'lingkungan': 'Lingkungan',
    'lainnya': 'Lainnya'
};

// Map Modal State
const showMapModal = ref(false);

const openMapModal = () => {
    showMapModal.value = true;
};

// Document Preview State
const showDocPreview = ref(false);
const previewDocUrl = ref('');
const previewDocTitle = ref('');

const openDocPreview = (doc) => {
    previewDocTitle.value = doc.document_type;
    previewDocUrl.value = route('superadmin.verifications.document', { kos: props.kos.id, document: doc.id });
    showDocPreview.value = true;
};

// Legal Docs Modal State
const showLegalDocsModal = ref(false);

// Grouped Rooms Computation
const groupedRooms = computed(() => {
    if (!props.kos.rooms) return [];
    
    const groups = {};
    props.kos.rooms.forEach(room => {
        const roomName = room.name || 'Kamar Standar';
        const key = `${roomName}-${room.price}-${room.price_period}-${room.capacity}`;
        
        if (!groups[key]) {
            groups[key] = {
                ...room,
                name: roomName,
                count: 1,
                room_numbers: [room.room_number]
            };
        } else {
            groups[key].count++;
            groups[key].room_numbers.push(room.room_number);
        }
    });
    
    return Object.values(groups).map(group => {
        let display_numbers = group.room_numbers.slice(0, 5).join(', ');
        if (group.room_numbers.length > 5) {
            display_numbers += ` ... (+${group.room_numbers.length - 5} lainnya)`;
        }
        return {
            ...group,
            display_numbers
        };
    });
});
</script>

<template>
    <AppLayout>
        <Head :title="`Verifikasi: ${kos.name}`" />

        <div class="max-w-4xl mx-auto space-y-4">
            <!-- Header Ringkas -->
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <Link :href="route('superadmin.verifications.index')">
                        <Button variant="ghost" size="icon" class="rounded-full -ml-2 text-gray-500 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white"><ChevronLeft class="w-5 h-5" /></Button>
                    </Link>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Verifikasi: {{ kos.name }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider border"
                        :class="{
                            'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-800': kos.status === 'menunggu_verifikasi',
                            'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800': kos.status === 'dipublikasikan',
                            'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800': kos.status === 'ditolak'
                        }">
                        {{ kos.status.replace('_', ' ') }}
                    </span>
                </div>
            </div>

            <!-- Revisi Warning -->
            <div v-if="kos.pending_revisions" class="bg-blue-50/50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-900/50 flex items-start gap-3">
                <AlertTriangle class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                <div>
                    <h4 class="font-bold text-blue-800 dark:text-blue-300 text-sm">Perhatian: Tinjauan Revisi Data</h4>
                    <p class="text-blue-700 dark:text-blue-400 text-xs mt-1">Pengajuan ini merupakan perubahan data dari properti yang sudah ada. Menyetujui pengajuan ini akan memperbarui data publik.</p>
                </div>
            </div>

            <!-- Single Clean Card -->
            <Card class="shadow-sm border-gray-200 dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <CardContent class="p-0 border-0">
                    <!-- Pihak Terkait & Wilayah (Grid) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x dark:divide-slate-800 border-b border-gray-100 dark:border-slate-800">
                        <div class="p-6 hover:bg-gray-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <p class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-3">Data Pemilik</p>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/10 dark:bg-primary/20 flex items-center justify-center font-bold text-primary dark:text-blue-400 shrink-0">
                                    {{ kos.admin?.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ kos.admin?.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ kos.admin?.email }}</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400 truncate">{{ kos.admin?.whatsapp_number || '-' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 hover:bg-gray-50/50 dark:hover:bg-slate-800/50 transition-colors">
                            <p class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-3">Wilayah Properti</p>
                            <div class="flex items-start gap-2">
                                <MapPin class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ kos.city }}</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ kos.district }}, {{ kos.subdistrict }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Dasar & Media -->
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800 space-y-4">
                        <p class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2">Informasi Properti</p>
                        
                        <div class="bg-gray-50 dark:bg-slate-800/50 rounded-lg border border-gray-100 dark:border-slate-700 p-4">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">Alamat Lengkap</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ kos.address }}</p>
                            
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">Deskripsi</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap leading-relaxed">{{ kos.description }}</p>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row flex-wrap gap-2 pt-2">
                            <Button @click="openPhotoSlider(0)" variant="outline" size="sm" class="w-full sm:w-auto text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900 hover:bg-blue-50 dark:hover:bg-blue-900/50 bg-blue-50/30 dark:bg-blue-900/20">
                                <ImageIcon class="w-4 h-4 mr-2" /> {{ kos.photos?.length || 0 }} Foto Galeri
                            </Button>
                            <Button @click="openMapModal" variant="outline" size="sm" class="w-full sm:w-auto text-green-700 dark:text-green-400 border-green-200 dark:border-green-900 hover:bg-green-50 dark:hover:bg-green-900/50 bg-green-50/30 dark:bg-green-900/20">
                                <Map class="w-4 h-4 mr-2" /> Tinjau Titik Peta
                            </Button>
                            <Button @click="showLegalDocsModal = true" variant="outline" size="sm" class="w-full sm:w-auto text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-900 hover:bg-purple-50 dark:hover:bg-purple-900/50 bg-purple-50/30 dark:bg-purple-900/20">
                                <FileText class="w-4 h-4 mr-2" /> {{ kos.legal_documents?.length || 0 }} Dokumen Legal
                            </Button>
                        </div>
                    </div>

                    <!-- Fasilitas & Kamar -->
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-3">Fasilitas Umum</p>
                                <div v-if="kos.facilities?.length > 0" class="flex flex-wrap gap-2">
                                    <span v-for="f in kos.facilities" :key="f.id" class="px-2.5 py-1 bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded text-xs text-gray-700 dark:text-slate-300 flex items-center gap-1.5">
                                        <span v-html="f.icon" class="w-3.5 h-3.5"></span> {{ f.name }}
                                    </span>
                                </div>
                                <p v-else class="text-xs text-gray-500 dark:text-slate-500 italic">Tidak ada fasilitas.</p>

                                <p class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-3 mt-6">Peraturan Kos</p>
                                <div v-if="kos.rules?.length > 0" class="flex flex-wrap gap-2">
                                    <span v-for="r in kos.rules" :key="r.id" class="px-2.5 py-1 bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded text-xs flex items-center gap-1.5 font-medium" :class="r.is_positive ? 'text-teal-700 dark:text-teal-400' : 'text-red-600 dark:text-red-400'">
                                        <Check v-if="r.is_positive" class="w-3.5 h-3.5 shrink-0" />
                                        <X v-else class="w-3.5 h-3.5 shrink-0" />
                                        {{ r.name }}
                                    </span>
                                </div>
                                <p v-else class="text-xs text-gray-500 dark:text-slate-500 italic">Tidak ada peraturan.</p>
                            </div>
                            
                            <div>
                                <p class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-3">Daftar Kamar ({{ groupedRooms.length }} Tipe, Total {{ kos.rooms?.length || 0 }} Kamar)</p>
                                <div class="space-y-2">
                                    <div v-for="(group, idx) in groupedRooms" :key="idx" class="p-3 border border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/50 rounded-lg flex justify-between items-start">
                                        <div class="min-w-0 pr-2">
                                            <div class="flex items-center gap-2 mb-0.5">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-slate-200 truncate">{{ group.name }}</p>
                                                <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-400 rounded text-[10px] font-bold shrink-0">{{ group.count }} Kamar</span>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-slate-400">Kapasitas {{ group.capacity }} orang</p>
                                            <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-1 line-clamp-1" :title="'Semua Nomor: ' + group.room_numbers.join(', ')">No: {{ group.display_numbers }}</p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-sm font-bold text-primary dark:text-blue-400">Rp {{ Number(group.price).toLocaleString('id-ID') }}</p>
                                            <p class="text-[10px] text-gray-500 dark:text-slate-500 uppercase">/ {{ group.price_period }}</p>
                                        </div>
                                    </div>
                                    <p v-if="!kos.rooms?.length" class="text-xs text-gray-500 dark:text-slate-500 italic">Belum ada kamar yang didaftarkan.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="p-4 bg-gray-50/80 dark:bg-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3" v-if="kos.status === 'menunggu_verifikasi' || kos.pending_revisions">
                        <Button type="button" @click="showRejectForm = true" variant="ghost" class="w-full sm:w-auto text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 text-sm">
                            Tolak Pengajuan
                        </Button>
                        <Button type="button" @click="confirmingApproval = true" class="w-full sm:w-auto px-8 shadow-sm bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-500 text-white">
                            Setujui & Publikasikan
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Reject Modal -->
        <Dialog :open="showRejectForm" @update:open="val => showRejectForm = val">
            <DialogContent class="sm:max-w-md dark:bg-slate-900 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="flex items-center text-red-600 dark:text-red-400"><AlertTriangle class="w-5 h-5 mr-2" /> Tolak Pengajuan</DialogTitle>
                </DialogHeader>
                <div class="py-2">
                    <p class="text-sm text-gray-600 dark:text-slate-400 mb-3">Berikan alasan yang jelas mengapa pengajuan ini ditolak agar pemilik kos dapat memperbaikinya.</p>
                    <Textarea v-model="rejectForm.note" placeholder="Misal: Dokumen KTP tidak jelas, foto buram..." rows="4" class="resize-none dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                    <p v-if="rejectForm.errors.note" class="text-xs text-red-500 mt-1">{{ rejectForm.errors.note }}</p>
                </div>
                <DialogFooter class="flex flex-col sm:flex-row gap-2 mt-4">
                    <Button variant="ghost" @click="showRejectForm = false" class="w-full sm:w-auto dark:text-slate-300">Batal</Button>
                    <Button variant="destructive" @click="reject" :disabled="rejectForm.processing" class="w-full sm:w-auto">Kirim Penolakan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal List Dokumen Legalitas -->
        <Dialog :open="showLegalDocsModal" @update:open="val => showLegalDocsModal = val">
            <DialogContent class="sm:max-w-md dark:bg-slate-900 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="flex items-center text-gray-900 dark:text-white"><FileText class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" /> Dokumen Legalitas</DialogTitle>
                </DialogHeader>
                <div class="py-2">
                    <div v-if="!kos.legal_documents || kos.legal_documents.length === 0" class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-100 dark:border-red-900/50 flex items-center gap-3">
                        <AlertTriangle class="w-5 h-5 text-red-500 shrink-0" />
                        <p class="text-sm text-red-700 dark:text-red-400 font-medium">Kos tidak melampirkan dokumen legalitas.</p>
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="doc in kos.legal_documents" :key="doc.id" 
                            class="flex items-center justify-between p-3 border border-gray-200 dark:border-slate-700 rounded-lg hover:border-blue-300 dark:hover:border-blue-700 hover:bg-blue-50/30 dark:hover:bg-blue-900/20 transition-colors cursor-pointer group"
                            @click="openDocPreview(doc)">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                    <FileText class="w-4 h-4" />
                                </div>
                                <p class="text-sm font-medium text-gray-900 dark:text-slate-200 truncate group-hover:text-blue-700 dark:group-hover:text-blue-300">{{ doc.document_type }}</p>
                            </div>
                            <Eye class="w-4 h-4 text-gray-400 dark:text-slate-500 group-hover:text-blue-600 dark:group-hover:text-blue-400 shrink-0" />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-slate-500 text-center mt-4">Ketuk dokumen untuk melihat pratinjau.</p>
                    </div>
                </div>
                <DialogFooter class="flex flex-col sm:flex-row gap-2 mt-4">
                    <Button variant="outline" @click="showLegalDocsModal = false" class="w-full sm:w-auto dark:border-slate-700 dark:text-slate-300">Tutup</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal Slider Foto -->
        <Dialog :open="showPhotoSlider" @update:open="val => showPhotoSlider = val">
            <DialogContent class="max-w-5xl bg-black/95 p-0 border-none shadow-2xl overflow-hidden h-[90vh] flex flex-col justify-center">
                <DialogHeader class="absolute top-4 left-4 z-50 text-white drop-shadow-md">
                    <DialogTitle class="text-white text-lg">Galeri Foto</DialogTitle>
                    <DialogDescription class="text-gray-300">
                        {{ currentPhotoIndex + 1 }} dari {{ kos.photos?.length }}
                    </DialogDescription>
                </DialogHeader>
                
                <div class="relative flex-grow flex items-center justify-center p-4 h-full w-full" v-if="kos.photos?.length > 0">
                    <img :src="kos.photos[currentPhotoIndex].file_path" class="max-h-full max-w-full object-contain drop-shadow-lg" />
                    
                    <!-- Kategori Foto (Sangat Jelas di Bawah Tengah) -->
                    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 bg-black/70 backdrop-blur-md text-white px-6 py-2 rounded-full font-bold tracking-wide border border-white/30 shadow-2xl z-50 flex items-center gap-2">
                        <ImageIcon class="w-4 h-4 opacity-80" />
                        {{ photoCategories[kos.photos[currentPhotoIndex].category] || 'Lainnya' }}
                    </div>
                    
                    <Button variant="secondary" size="icon" @click="prevPhoto" class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full opacity-70 hover:opacity-100 transition-opacity">
                        <ChevronLeft class="w-6 h-6" />
                    </Button>
                    <Button variant="secondary" size="icon" @click="nextPhoto" class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full opacity-70 hover:opacity-100 transition-opacity">
                        <ChevronRight class="w-6 h-6" />
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Modal Preview Dokumen -->
        <Dialog :open="showDocPreview" @update:open="val => showDocPreview = val">
            <DialogContent :hide-overlay="true" class="max-w-4xl h-[85vh] flex flex-col p-0 overflow-hidden shadow-2xl border-gray-300 dark:border-slate-800 dark:bg-slate-900">
                <DialogHeader class="p-4 border-b dark:border-slate-800 bg-gray-50 dark:bg-slate-800/50 shrink-0">
                    <DialogTitle class="flex items-center dark:text-white">
                        <FileText class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" /> Preview: {{ previewDocTitle }}
                    </DialogTitle>
                </DialogHeader>
                <div class="flex-grow w-full h-full bg-gray-100/50 dark:bg-slate-900 p-4 flex items-center justify-center">
                    <iframe v-if="previewDocUrl" :src="previewDocUrl" class="w-full h-full border dark:border-slate-700 rounded shadow-sm bg-white"></iframe>
                </div>
                <DialogFooter class="p-4 border-t dark:border-slate-800 bg-gray-50 dark:bg-slate-800/50 shrink-0 flex flex-col sm:flex-row justify-between sm:justify-between items-center gap-4">
                    <a :href="previewDocUrl" target="_blank" download class="w-full sm:w-auto text-center sm:text-left text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 flex items-center justify-center sm:justify-start">
                        <Download class="w-4 h-4 mr-1" /> Unduh Dokumen
                    </a>
                    <Button variant="outline" @click="showDocPreview = false" class="w-full sm:w-auto dark:border-slate-700 dark:text-slate-300">Tutup Preview</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal Peta -->
        <Dialog :open="showMapModal" @update:open="val => showMapModal = val">
            <DialogContent class="max-w-3xl flex flex-col p-0 overflow-hidden dark:border-slate-800">
                <DialogHeader class="p-4 border-b dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shrink-0">
                    <DialogTitle class="flex items-center dark:text-white">
                        <Map class="w-5 h-5 mr-2 text-primary dark:text-blue-400" /> Lokasi Properti: {{ kos.name }}
                    </DialogTitle>
                </DialogHeader>
                <div class="w-full bg-gray-100 dark:bg-slate-950 p-0 m-0 border-none">
                    <MapPicker 
                        v-if="showMapModal"
                        :model-value="{ lat: kos.latitude || -6.200000, lng: kos.longitude || 106.816666 }" 
                        :readonly="true" 
                        class="w-full border-none"
                    />
                </div>
                <DialogFooter class="p-4 border-t dark:border-slate-800 bg-gray-50 dark:bg-slate-900 shrink-0 flex flex-col sm:flex-row gap-2">
                    <Button variant="outline" @click="showMapModal = false" class="w-full sm:w-auto dark:border-slate-700 dark:text-slate-300">Tutup Peta</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal Konfirmasi Setujui -->
        <Dialog :open="confirmingApproval" @update:open="val => confirmingApproval = val">
            <DialogContent class="sm:max-w-md dark:bg-slate-900 dark:border-slate-800">
                <DialogHeader>
                    <div class="flex items-center gap-3 mb-2 text-green-600 dark:text-green-400">
                        <div class="p-2 bg-green-100 dark:bg-green-900/50 rounded-full shrink-0">
                            <CheckCircle class="w-5 h-5" />
                        </div>
                        <DialogTitle class="dark:text-white">Setujui & Publikasikan?</DialogTitle>
                    </div>
                </DialogHeader>
                
                <div class="py-2 text-sm text-gray-600 dark:text-slate-400">
                    Apakah Anda yakin ingin menyetujui pengajuan ini? Properti <strong class="dark:text-slate-200">{{ kos.name }}</strong> akan dipublikasikan dan dapat dilihat oleh semua calon penyewa.
                </div>

                <DialogFooter class="mt-4 flex flex-col sm:flex-row gap-2">
                    <Button variant="outline" @click="confirmingApproval = false" class="w-full sm:w-auto dark:text-slate-300 dark:border-slate-700">Batal</Button>
                    <Button class="w-full sm:w-auto bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-500 text-white" @click="approve">Ya, Publikasikan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
