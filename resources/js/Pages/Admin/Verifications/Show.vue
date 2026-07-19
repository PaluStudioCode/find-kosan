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
</script>

<template>
    <AppLayout>
        <Head :title="`Tinjau Kos - ${kos.name}`" />

        <!-- Header Actions -->
        <div class="sticky top-0 z-10 bg-white/80 backdrop-blur-md border-b pb-4 pt-4 mb-6 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 flex flex-col md:flex-row justify-between md:items-center gap-4">
            <div>
                <Link :href="route('admin.verifications.index')" class="text-sm text-gray-500 hover:text-primary transition-colors flex items-center mb-1 w-fit">
                    <ChevronLeft class="w-4 h-4 mr-1" /> Kembali ke Daftar
                </Link>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ kos.name }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium border"
                        :class="{
                            'bg-orange-50 text-orange-700 border-orange-200': kos.status === 'menunggu_verifikasi',
                            'bg-green-50 text-green-700 border-green-200': kos.status === 'disetujui',
                            'bg-red-50 text-red-700 border-red-200': kos.status === 'ditolak'
                        }">
                        {{ kos.status.replace('_', ' ').toUpperCase() }}
                    </span>
                </div>
            </div>
            
            <div v-if="kos.status === 'menunggu_verifikasi'" class="flex flex-wrap gap-2">
                <Button variant="outline" class="text-red-600 border-red-200 hover:bg-red-50" @click="showRejectForm = !showRejectForm">
                    <XCircle class="w-4 h-4 mr-2" /> Tolak Pengajuan
                </Button>
                <Button class="bg-green-600 hover:bg-green-700 text-white shadow-sm" @click="confirmingApproval = true">
                    <CheckCircle class="w-4 h-4 mr-2" /> Setujui & Publikasikan
                </Button>
            </div>
        </div>

        <!-- Reject Form Dropdown -->
        <div v-if="showRejectForm" class="bg-red-50 p-5 rounded-xl border border-red-200 mb-8 shadow-sm">
            <h4 class="font-semibold text-red-800 mb-2 flex items-center">
                <AlertTriangle class="w-5 h-5 mr-2" /> Alasan Penolakan
            </h4>
            <Textarea v-model="rejectForm.note" placeholder="Tuliskan alasan penolakan secara spesifik agar pemilik kos dapat memperbaikinya..." rows="3" class="mb-3 bg-white" />
            <p v-if="rejectForm.errors.note" class="text-sm text-red-500 mb-3">{{ rejectForm.errors.note }}</p>
            <div class="flex justify-end gap-2">
                <Button variant="ghost" class="text-red-700 hover:bg-red-100" @click="showRejectForm = false">Batal</Button>
                <Button variant="destructive" @click="reject" :disabled="rejectForm.processing">Kirim Penolakan</Button>
            </div>
        </div>

        <div v-if="kos.pending_revisions" class="bg-blue-50 p-5 rounded-xl border border-blue-200 mb-8 shadow-sm">
            <h4 class="font-bold text-blue-800 mb-2 flex items-center">
                <AlertTriangle class="w-5 h-5 mr-2" /> Perhatian: Tinjauan Revisi Data
            </h4>
            <p class="text-blue-700 text-sm mb-4">Pengajuan ini merupakan perubahan data dari properti yang sudah dipublikasikan. Menyetujui pengajuan ini akan menimpa data lama dengan data baru yang diajukan.</p>
        </div>



        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Details -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Info Utama -->
                <Card class="border-none shadow-sm ring-1 ring-gray-100">
                    <CardHeader class="pb-4">
                        <CardTitle class="flex items-center text-xl">
                            <Home class="w-5 h-5 mr-2 text-primary" /> Informasi Properti
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-5 rounded-xl border border-gray-100">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Pemilik</p>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                                        {{ kos.owner?.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ kos.owner?.name }}</p>
                                        <p class="text-xs text-gray-500">{{ kos.owner?.email }}</p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Wilayah</p>
                                <p class="font-medium text-gray-900 flex items-start">
                                    <MapPin class="w-4 h-4 mr-1 text-gray-400 shrink-0 mt-0.5" />
                                    <span>{{ kos.city }}, {{ kos.district }}, {{ kos.subdistrict }}</span>
                                </p>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-sm font-semibold text-gray-900 mb-2">Alamat Lengkap</p>
                            <p class="text-sm text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-100">{{ kos.address }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-gray-900 mb-2">Deskripsi</p>
                            <div class="text-sm text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-100 whitespace-pre-line leading-relaxed mb-6">
                                {{ kos.description }}
                            </div>
                        </div>
                        
                        <div class="pt-2 border-t border-gray-100 flex flex-wrap gap-3">
                            <Button @click="openPhotoSlider(0)" variant="outline" class="flex-1 sm:flex-none border-blue-200 text-blue-700 hover:bg-blue-50">
                                <ImageIcon class="w-4 h-4 mr-2" /> Lihat Galeri Foto
                            </Button>
                            <Button @click="openMapModal" variant="outline" class="flex-1 sm:flex-none border-green-200 text-green-700 hover:bg-green-50">
                                <Map class="w-4 h-4 mr-2" /> Tinjau Lokasi Peta
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Fasilitas & Kamar -->
                <Card class="border-none shadow-sm ring-1 ring-gray-100">
                    <CardHeader class="pb-4">
                        <CardTitle class="text-xl">Fasilitas & Kamar</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-8">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 mb-3">Fasilitas Umum Kos</p>
                            <div v-if="kos.facilities && kos.facilities.length > 0" class="flex flex-wrap gap-2">
                                <span v-for="f in kos.facilities" :key="f.id" class="px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 flex items-center gap-1.5 font-medium shadow-sm">
                                    <span v-html="f.icon" class="w-4 h-4"></span> {{ f.name }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-500 italic">Tidak ada fasilitas umum.</p>
                        </div>
                        
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-semibold text-gray-900">Tipe Kamar</p>
                                <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2 py-1 rounded-md">{{ kos.rooms?.length || 0 }} Tipe</span>
                            </div>
                            
                            <div class="space-y-3">
                                <div v-for="room in kos.rooms" :key="room.id" class="p-4 border border-gray-100 bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <p class="font-bold text-gray-900">{{ room.name }}</p>
                                            <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full font-semibold">{{ room.room_number }}</span>
                                        </div>
                                        <p class="text-sm text-gray-500 flex items-center">
                                            <User class="w-3.5 h-3.5 mr-1" /> Kapasitas {{ room.capacity }} orang
                                        </p>
                                    </div>
                                    <div class="sm:text-right bg-gray-50 sm:bg-transparent p-3 sm:p-0 rounded-lg">
                                        <p class="font-bold text-lg text-primary">Rp {{ Number(room.price).toLocaleString('id-ID') }}</p>
                                        <p class="text-xs text-gray-500 font-medium">/ {{ room.price_period }}</p>
                                    </div>
                                </div>
                                <div v-if="!kos.rooms || kos.rooms.length === 0" class="text-sm text-gray-500 italic text-center p-6 border rounded-xl bg-gray-50">
                                    Belum ada kamar yang ditambahkan.
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Column: Legal Docs & Actions -->
            <div class="space-y-6">
                <!-- Legal Docs -->
                <Card class="border-none shadow-sm ring-1 ring-gray-100 sticky top-[100px]">
                    <CardHeader class="pb-4 bg-gray-50/50 border-b border-gray-100">
                        <CardTitle class="flex items-center text-lg">
                            <FileText class="w-5 h-5 mr-2 text-blue-600" /> Dokumen Legalitas
                        </CardTitle>
                        <CardDescription>Dokumen verifikasi untuk memeriksa keabsahan properti kos ini.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div v-if="!kos.legal_documents || kos.legal_documents.length === 0" class="text-center p-6 bg-red-50 rounded-xl border border-red-100">
                            <AlertTriangle class="w-8 h-8 text-red-400 mx-auto mb-2" />
                            <p class="text-sm text-red-700 font-medium">Tidak ada dokumen legalitas terlampir.</p>
                            <p class="text-xs text-red-500 mt-1">Kos tidak memenuhi syarat verifikasi.</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="doc in kos.legal_documents" :key="doc.id" 
                                class="group flex items-center justify-between p-3 border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-blue-200 transition-colors cursor-pointer"
                                @click="openDocPreview(doc)">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                        <FileText class="w-5 h-5" />
                                    </div>
                                    <div class="truncate">
                                        <p class="font-semibold text-sm text-gray-900 truncate">{{ doc.document_type }}</p>
                                        <p class="text-xs text-gray-500">Ketuk untuk melihat</p>
                                    </div>
                                </div>
                                <Button variant="ghost" size="sm" class="shrink-0 rounded-full w-8 h-8 p-0 text-gray-400 group-hover:text-blue-600 group-hover:bg-blue-100">
                                    <Eye class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-gray-100" v-if="kos.status === 'menunggu_verifikasi'">
                            <p class="text-xs text-gray-500 mb-3 text-center">Pastikan semua dokumen asli dan sah sebelum menyetujui.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

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
            <DialogContent class="max-w-4xl h-[85vh] flex flex-col p-0 overflow-hidden">
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
                <div class="w-full bg-gray-100 p-0 m-0 border-none">
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
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <div class="flex items-center gap-4 mb-2 text-green-600">
                        <div class="p-3 bg-green-100 rounded-full shrink-0">
                            <CheckCircle class="w-6 h-6" />
                        </div>
                        <DialogTitle>Setujui & Publikasikan?</DialogTitle>
                    </div>
                </DialogHeader>
                
                <DialogDescription class="text-sm">
                    Apakah Anda yakin ingin menyetujui pengajuan ini? Properti <strong>{{ kos.name }}</strong> akan dipublikasikan dan dapat dilihat oleh semua calon penyewa.
                </DialogDescription>

                <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                    <Button variant="outline" @click="confirmingApproval = false">Batal</Button>
                    <Button class="bg-green-600 hover:bg-green-700 text-white" @click="approve">Ya, Setujui & Publikasikan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
