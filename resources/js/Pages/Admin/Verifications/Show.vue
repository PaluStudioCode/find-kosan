<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { ChevronLeft, ChevronRight, Download, CheckCircle, XCircle, FileText, Eye, MapPin, Home, User, Map, Check, AlertTriangle, Image as ImageIcon } from 'lucide-vue-next';
import { useToast } from '@/components/ui/toast/use-toast';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
    kos: Object
});

const { toast } = useToast();
const rejectForm = useForm({ note: '' });
const showRejectForm = ref(false);

const confirmingApproval = ref(false);

const approve = () => {
    router.post(route('admin.verifications.approve', props.kos.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            confirmingApproval.value = false;
            toast({ title: 'Berhasil', description: 'Kos disetujui dan dipublikasikan.' });
        },
    });
};

const reject = () => {
    rejectForm.post(route('admin.verifications.reject', props.kos.id), {
        onSuccess: () => {
            toast({ title: 'Berhasil', description: 'Kos ditolak.' });
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
    previewDocUrl.value = route('admin.verifications.document', { kos: props.kos.id, document: doc.id });
    showDocPreview.value = true;
};

// Legal Docs Modal State
const showLegalDocsModal = ref(false);
</script>

<template>
    <AppLayout>
        <Head :title="`Verifikasi: ${kos.name}`" />

        <div class="max-w-4xl mx-auto space-y-4">
            <!-- Header Ringkas -->
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.verifications.index')">
                        <Button variant="ghost" size="icon" class="rounded-full -ml-2 text-gray-500 hover:text-gray-900"><ChevronLeft class="w-5 h-5" /></Button>
                    </Link>
                    <h2 class="text-xl font-semibold text-gray-900">Verifikasi: {{ kos.name }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider border"
                        :class="{
                            'bg-orange-50 text-orange-700 border-orange-200': kos.status === 'menunggu_verifikasi',
                            'bg-green-50 text-green-700 border-green-200': kos.status === 'disetujui',
                            'bg-red-50 text-red-700 border-red-200': kos.status === 'ditolak'
                        }">
                        {{ kos.status.replace('_', ' ') }}
                    </span>
                </div>
            </div>

            <!-- Revisi Warning -->
            <div v-if="kos.pending_revisions" class="bg-blue-50/50 p-4 rounded-lg border border-blue-100 flex items-start gap-3">
                <AlertTriangle class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                <div>
                    <h4 class="font-bold text-blue-800 text-sm">Perhatian: Tinjauan Revisi Data</h4>
                    <p class="text-blue-700 text-xs mt-1">Pengajuan ini merupakan perubahan data dari properti yang sudah ada. Menyetujui pengajuan ini akan memperbarui data publik.</p>
                </div>
            </div>

            <!-- Single Clean Card -->
            <Card class="shadow-sm border-gray-200 overflow-hidden">
                <CardContent class="p-0">
                    <!-- Pihak Terkait & Wilayah (Grid) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x border-b border-gray-100">
                        <div class="p-6 hover:bg-gray-50/50 transition-colors">
                            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">Data Pemilik</p>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary shrink-0">
                                    {{ kos.owner?.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ kos.owner?.name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ kos.owner?.email }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ kos.owner?.whatsapp_number || '-' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 hover:bg-gray-50/50 transition-colors">
                            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">Wilayah Properti</p>
                            <div class="flex items-start gap-2">
                                <MapPin class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ kos.city }}</p>
                                    <p class="text-xs text-gray-500">{{ kos.district }}, {{ kos.subdistrict }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Dasar & Media -->
                    <div class="p-6 border-b border-gray-100 space-y-4">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Informasi Properti</p>
                        
                        <div class="bg-gray-50 rounded-lg border border-gray-100 p-4">
                            <p class="text-sm font-medium text-gray-800 mb-1">Alamat Lengkap</p>
                            <p class="text-sm text-gray-600 mb-4">{{ kos.address }}</p>
                            
                            <p class="text-sm font-medium text-gray-800 mb-1">Deskripsi</p>
                            <p class="text-sm text-gray-600 whitespace-pre-wrap leading-relaxed">{{ kos.description }}</p>
                        </div>
                        
                        <div class="flex flex-wrap gap-2 pt-2">
                            <Button @click="openPhotoSlider(0)" variant="outline" size="sm" class="text-blue-700 border-blue-200 hover:bg-blue-50 bg-blue-50/30">
                                <ImageIcon class="w-4 h-4 mr-2" /> {{ kos.photos?.length || 0 }} Foto Galeri
                            </Button>
                            <Button @click="openMapModal" variant="outline" size="sm" class="text-green-700 border-green-200 hover:bg-green-50 bg-green-50/30">
                                <Map class="w-4 h-4 mr-2" /> Tinjau Titik Peta
                            </Button>
                            <Button @click="showLegalDocsModal = true" variant="outline" size="sm" class="text-purple-700 border-purple-200 hover:bg-purple-50 bg-purple-50/30">
                                <FileText class="w-4 h-4 mr-2" /> {{ kos.legal_documents?.length || 0 }} Dokumen Legal
                            </Button>
                        </div>
                    </div>

                    <!-- Fasilitas & Kamar -->
                    <div class="p-6 border-b border-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">Fasilitas Umum</p>
                                <div v-if="kos.facilities?.length > 0" class="flex flex-wrap gap-2">
                                    <span v-for="f in kos.facilities" :key="f.id" class="px-2.5 py-1 bg-gray-50 border border-gray-200 rounded text-xs text-gray-700 flex items-center gap-1.5">
                                        <span v-html="f.icon" class="w-3.5 h-3.5"></span> {{ f.name }}
                                    </span>
                                </div>
                                <p v-else class="text-xs text-gray-500 italic">Tidak ada fasilitas.</p>
                            </div>
                            
                            <div>
                                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">Daftar Kamar ({{ kos.rooms?.length || 0 }} Tipe)</p>
                                <div class="space-y-2">
                                    <div v-for="room in kos.rooms" :key="room.id" class="p-3 border border-gray-100 bg-gray-50/50 rounded-lg flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ room.name }}</p>
                                            <p class="text-xs text-gray-500">Kapasitas {{ room.capacity }} orang</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-primary">Rp {{ Number(room.price).toLocaleString('id-ID') }}</p>
                                            <p class="text-[10px] text-gray-500 uppercase">/ {{ room.price_period }}</p>
                                        </div>
                                    </div>
                                    <p v-if="!kos.rooms?.length" class="text-xs text-gray-500 italic">Belum ada kamar yang didaftarkan.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="p-4 bg-gray-50/80 flex flex-col sm:flex-row items-center justify-between gap-3" v-if="kos.status === 'menunggu_verifikasi'">
                        <Button type="button" @click="showRejectForm = true" variant="ghost" class="text-red-600 hover:text-red-700 hover:bg-red-50 text-sm">
                            Tolak Pengajuan
                        </Button>
                        <Button type="button" @click="confirmingApproval = true" class="w-full sm:w-auto px-8 shadow-sm bg-green-600 hover:bg-green-700 text-white">
                            Setujui & Publikasikan
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Reject Modal -->
        <Dialog :open="showRejectForm" @update:open="val => showRejectForm = val">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center text-red-600"><AlertTriangle class="w-5 h-5 mr-2" /> Tolak Pengajuan</DialogTitle>
                </DialogHeader>
                <div class="py-2">
                    <p class="text-sm text-gray-600 mb-3">Berikan alasan yang jelas mengapa pengajuan ini ditolak agar pemilik kos dapat memperbaikinya.</p>
                    <Textarea v-model="rejectForm.note" placeholder="Misal: Dokumen KTP tidak jelas, foto buram..." rows="4" class="resize-none" />
                    <p v-if="rejectForm.errors.note" class="text-xs text-red-500 mt-1">{{ rejectForm.errors.note }}</p>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="showRejectForm = false">Batal</Button>
                    <Button variant="destructive" @click="reject" :disabled="rejectForm.processing">Kirim Penolakan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal List Dokumen Legalitas -->
        <Dialog :open="showLegalDocsModal" @update:open="val => showLegalDocsModal = val">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center text-gray-900"><FileText class="w-5 h-5 mr-2 text-purple-600" /> Dokumen Legalitas</DialogTitle>
                </DialogHeader>
                <div class="py-2">
                    <div v-if="!kos.legal_documents || kos.legal_documents.length === 0" class="p-4 bg-red-50 rounded-lg border border-red-100 flex items-center gap-3">
                        <AlertTriangle class="w-5 h-5 text-red-500 shrink-0" />
                        <p class="text-sm text-red-700 font-medium">Kos tidak melampirkan dokumen legalitas.</p>
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="doc in kos.legal_documents" :key="doc.id" 
                            class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50/30 transition-colors cursor-pointer group"
                            @click="openDocPreview(doc)">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                    <FileText class="w-4 h-4" />
                                </div>
                                <p class="text-sm font-medium text-gray-900 truncate group-hover:text-blue-700">{{ doc.document_type }}</p>
                            </div>
                            <Eye class="w-4 h-4 text-gray-400 group-hover:text-blue-600 shrink-0" />
                        </div>
                        <p class="text-xs text-gray-500 text-center mt-4">Ketuk dokumen untuk melihat pratinjau.</p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showLegalDocsModal = false">Tutup</Button>
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
            <DialogContent :hide-overlay="true" class="max-w-4xl h-[85vh] flex flex-col p-0 overflow-hidden shadow-2xl border-gray-300">
                <DialogHeader class="p-4 border-b bg-gray-50 shrink-0">
                    <DialogTitle class="flex items-center">
                        <FileText class="w-5 h-5 mr-2 text-blue-600" /> Preview: {{ previewDocTitle }}
                    </DialogTitle>
                </DialogHeader>
                <div class="flex-grow w-full h-full bg-gray-100/50 p-4 flex items-center justify-center">
                    <iframe v-if="previewDocUrl" :src="previewDocUrl" class="w-full h-full border rounded shadow-sm bg-white"></iframe>
                </div>
                <DialogFooter class="p-4 border-t bg-gray-50 shrink-0 flex justify-between sm:justify-between items-center">
                    <a :href="previewDocUrl" target="_blank" download class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center">
                        <Download class="w-4 h-4 mr-1" /> Unduh Dokumen
                    </a>
                    <Button variant="outline" @click="showDocPreview = false">Tutup Preview</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal Peta -->
        <Dialog :open="showMapModal" @update:open="val => showMapModal = val">
            <DialogContent class="max-w-3xl flex flex-col p-0 overflow-hidden">
                <DialogHeader class="p-4 border-b bg-gray-50 shrink-0">
                    <DialogTitle class="flex items-center">
                        <Map class="w-5 h-5 mr-2 text-primary" /> Lokasi Properti: {{ kos.name }}
                    </DialogTitle>
                </DialogHeader>
                <div class="w-full bg-gray-100 p-0 m-0 border-none h-[60vh]">
                    <MapPicker 
                        v-if="showMapModal"
                        :model-value="{ lat: kos.latitude || -6.200000, lng: kos.longitude || 106.816666 }" 
                        :readonly="true" 
                        class="w-full h-full border-none"
                    />
                </div>
                <DialogFooter class="p-4 border-t bg-gray-50 shrink-0">
                    <Button variant="outline" @click="showMapModal = false">Tutup Peta</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal Konfirmasi Setujui -->
        <Dialog :open="confirmingApproval" @update:open="val => confirmingApproval = val">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div class="flex items-center gap-3 mb-2 text-green-600">
                        <div class="p-2 bg-green-100 rounded-full shrink-0">
                            <CheckCircle class="w-5 h-5" />
                        </div>
                        <DialogTitle>Setujui & Publikasikan?</DialogTitle>
                    </div>
                </DialogHeader>
                
                <div class="py-2 text-sm text-gray-600">
                    Apakah Anda yakin ingin menyetujui pengajuan ini? Properti <strong>{{ kos.name }}</strong> akan dipublikasikan dan dapat dilihat oleh semua calon penyewa.
                </div>

                <DialogFooter class="mt-2">
                    <Button variant="outline" @click="confirmingApproval = false">Batal</Button>
                    <Button class="bg-green-600 hover:bg-green-700 text-white" @click="approve">Ya, Publikasikan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
