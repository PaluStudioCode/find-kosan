<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { useToast } from '@/components/ui/toast/use-toast';
import { Trash2, Star, UploadCloud, ImagePlus, X, AlertTriangle } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';

const props = defineProps({
    kos: Object,
    isLocked: Boolean
});

const { toast } = useToast();

// --- Gallery Photo (Multiple) ---
const selectedPhotos = ref([]);
const photoPreviews = ref([]);
const photoInputRef = ref(null);
const isUploadingPhotos = ref(false);

const handlePhotoSelect = (e) => {
    const files = Array.from(e.target.files);
    if (files.length === 0) return;

    for (const file of files) {
        if (file.size > 2 * 1024 * 1024) {
            toast({ title: 'Gagal', description: `File "${file.name}" melebihi batas 2MB.`, variant: 'destructive' });
            continue;
        }
        selectedPhotos.value.push(file);
        const reader = new FileReader();
        reader.onload = (ev) => {
            photoPreviews.value.push({ name: file.name, url: ev.target.result });
        };
        reader.readAsDataURL(file);
    }

    // Reset input so user can select same files again if needed
    if (photoInputRef.value) photoInputRef.value.value = '';
};

const removeSelectedPhoto = (index) => {
    selectedPhotos.value.splice(index, 1);
    photoPreviews.value.splice(index, 1);
};

const uploadPhotos = () => {
    if (selectedPhotos.value.length === 0) {
        toast({ title: 'Peringatan', description: 'Pilih minimal 1 foto untuk diunggah.', variant: 'destructive' });
        return;
    }

    isUploadingPhotos.value = true;

    const formData = new FormData();
    selectedPhotos.value.forEach((file) => {
        formData.append('photos[]', file);
    });

    router.post(route('owner.kos.photos.store', props.kos.id), formData, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            selectedPhotos.value = [];
            photoPreviews.value = [];
        },
        onError: (err) => {
            // Handle both single and array error formats
            const errorMsg = err.photos || err['photos.0'] || Object.values(err).flat().join(', ');
            toast({ title: 'Gagal', description: errorMsg || 'Terjadi kesalahan saat mengunggah.', variant: 'destructive' });
        },
        onFinish: () => {
            isUploadingPhotos.value = false;
        }
    });
};

const setMainPhoto = (photoId) => {
    router.put(route('owner.kos.photos.update', { kos: props.kos.id, photo: photoId }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Notifikasi sukses ditangani oleh layout global (flash.success)
        },
        onError: () => {
            toast({ title: 'Gagal', description: 'Gagal mengubah foto utama.', variant: 'destructive' });
        }
    });
};

const confirmingPhotoDeletion = ref(false);
const photoToDelete = ref(null);

const confirmPhotoDeletion = (photoId) => {
    photoToDelete.value = photoId;
    confirmingPhotoDeletion.value = true;
};

const closePhotoModal = () => {
    confirmingPhotoDeletion.value = false;
    setTimeout(() => {
        photoToDelete.value = null;
    }, 300);
};

const deletePhoto = () => {
    if (!photoToDelete.value) return;

    router.delete(route('owner.kos.photos.destroy', { kos: props.kos.id, photo: photoToDelete.value }), {
        preserveScroll: true,
        onSuccess: () => {
            closePhotoModal();
        },
        onError: () => {
            toast({ title: 'Gagal', description: 'Gagal menghapus foto.', variant: 'destructive' });
            closePhotoModal();
        }
    });
};

// --- QRIS (Single) ---
const qrisForm = useForm({
    qris_image: null,
});

const uploadQris = () => {
    qrisForm.post(route('owner.kos.qris', props.kos.id), {
        preserveScroll: true,
        onSuccess: () => {
            qrisForm.reset('qris_image');
        },
        onError: (err) => {
            if (err.qris_image) toast({ title: 'Gagal', description: err.qris_image, variant: 'destructive' });
        }
    });
};

const confirmingQrisDeletion = ref(false);

const confirmQrisDeletion = () => {
    confirmingQrisDeletion.value = true;
};

const closeQrisModal = () => {
    confirmingQrisDeletion.value = false;
};

const deleteQris = () => {
    router.delete(route('owner.kos.qris.destroy', props.kos.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeQrisModal();
        },
        onError: () => {
            toast({ title: 'Gagal', description: 'Gagal menghapus QRIS.', variant: 'destructive' });
            closeQrisModal();
        }
    });
};
</script>

<template>
    <div class="space-y-12">
        <div v-if="isLocked" class="bg-orange-50 border border-orange-200 text-orange-800 p-4 rounded-lg flex items-start mb-6">
            <div class="mr-3 mt-0.5 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            </div>
            <div>
                <h4 class="font-semibold">Data Sedang Ditinjau</h4>
                <p class="text-sm mt-1">Anda tidak dapat mengubah foto atau QRIS selama proses peninjauan oleh Super Admin berlangsung.</p>
            </div>
        </div>

        <!-- Foto Kos -->
        <fieldset :disabled="isLocked" :class="{ 'opacity-60': isLocked }">
            <div class="flex justify-between items-end mb-4 border-b pb-2">
                <div>
                    <h3 class="text-lg font-semibold">Galeri Foto Kos</h3>
                    <p class="text-sm text-gray-500">Unggah foto-foto menarik properti Anda. Foto dengan bintang adalah foto utama.</p>
                </div>
            </div>

            <!-- Upload Area -->
            <div class="mb-6 bg-gray-50 p-4 rounded border space-y-4">
                <div>
                    <Label class="mb-2 block">Pilih Foto (Bisa lebih dari 1, maks 2MB per file)</Label>
                    <Input 
                        ref="photoInputRef"
                        type="file" 
                        accept="image/*" 
                        multiple
                        @change="handlePhotoSelect"
                    />
                </div>

                <!-- Preview Selected -->
                <div v-if="photoPreviews.length > 0" class="space-y-3">
                    <p class="text-sm font-medium text-gray-700">{{ photoPreviews.length }} file dipilih:</p>
                    <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-3">
                        <div v-for="(preview, idx) in photoPreviews" :key="idx" class="relative group border rounded overflow-hidden aspect-square">
                            <img :src="preview.url" :alt="preview.name" class="w-full h-full object-cover" />
                            <button 
                                type="button" 
                                @click="removeSelectedPhoto(idx)"
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                <X class="w-3 h-3" />
                            </button>
                        </div>
                    </div>
                    <Button @click="uploadPhotos" :disabled="isUploadingPhotos">
                        <UploadCloud class="w-4 h-4 mr-2" /> 
                        {{ isUploadingPhotos ? 'Mengunggah...' : `Unggah ${photoPreviews.length} Foto` }}
                    </Button>
                </div>
            </div>

            <!-- Existing Photos Grid -->
            <div v-if="!kos.photos || kos.photos.length === 0" class="text-center py-8 text-gray-500 border rounded bg-gray-50 border-dashed">
                Belum ada foto yang diunggah.
            </div>
            
            <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <div v-for="photo in kos.photos" :key="photo.id" class="relative group border rounded overflow-hidden aspect-square">
                    <img :src="photo.file_path" class="w-full h-full object-cover" />
                    
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-center items-center gap-2">
                        <Button v-if="!photo.is_primary" size="sm" variant="outline" class="w-3/4 text-xs h-8 bg-white" @click="setMainPhoto(photo.id)">
                            Jadikan Utama
                        </Button>
                        <Button size="sm" variant="destructive" class="w-3/4 text-xs h-8" @click="confirmPhotoDeletion(photo.id)" :disabled="isLocked">
                            Hapus
                        </Button>
                    </div>

                    <div v-if="photo.is_primary" class="absolute top-2 right-2 bg-yellow-400 text-yellow-900 p-1 rounded-full shadow-md">
                        <Star class="w-4 h-4 fill-current" />
                    </div>
                </div>
            </div>

        <!-- Gambar QRIS -->
        <div class="pt-8 border-t">
            <h3 class="text-lg font-semibold mb-2">QRIS Pembayaran</h3>
            <p class="text-sm text-gray-500 mb-4 border-b pb-4">Unggah gambar QRIS Anda agar penyewa bisa memindai saat melakukan pembayaran. QRIS ini aman dan tidak tampil di halaman publik (hanya untuk penyewa Anda).</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <form @submit.prevent="uploadQris" class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="space-y-2 flex-grow">
                            <Label for="qris">Pilih Gambar QRIS</Label>
                            <Input id="qris" type="file" accept="image/*" @change="e => qrisForm.qris_image = e.target.files[0]" />
                            <p v-if="qrisForm.errors.qris_image" class="text-sm text-red-500">{{ qrisForm.errors.qris_image }}</p>
                        </div>
                        <Button type="submit" :disabled="qrisForm.processing || !qrisForm.qris_image || isLocked" class="shrink-0">
                            <UploadCloud class="w-4 h-4 mr-2" /> Unggah QRIS
                        </Button>
                    </form>
                </div>
                
                <div class="flex items-center justify-center border-2 border-dashed rounded p-4 bg-gray-50">
                    <div v-if="kos.payment_qris_image_path" class="text-center w-full">
                        <div class="flex justify-between items-center mb-2 px-2">
                            <p class="text-sm font-medium text-green-600">QRIS Tersimpan</p>
                            <Button size="sm" variant="destructive" class="h-7 text-xs px-2" @click="confirmQrisDeletion" :disabled="isLocked">
                                Hapus
                            </Button>
                        </div>
                        <div class="w-48 h-48 mx-auto flex items-center justify-center rounded border overflow-hidden bg-white">
                            <img :src="kos.payment_qris_image_path" alt="QRIS" class="w-full h-full object-contain" />
                        </div>
                    </div>
                    <div v-else class="text-gray-400 text-sm text-center">
                        Belum ada gambar QRIS.
                    </div>
                </div>
            </div>
        </div>
        </fieldset>
    </div>

    <!-- Modal Dialog Hapus Foto -->
    <Dialog :open="confirmingPhotoDeletion" @update:open="val => { if(!val) closePhotoModal(); }">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <div class="flex items-center gap-4 mb-2 text-destructive">
                    <div class="p-3 bg-destructive/10 rounded-full shrink-0">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <DialogTitle>Hapus Foto?</DialogTitle>
                </div>
            </DialogHeader>
            
            <DialogDescription class="text-sm">
                Apakah Anda yakin ingin menghapus foto ini? Tindakan ini tidak dapat dibatalkan.
            </DialogDescription>

            <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                <Button variant="outline" @click="closePhotoModal">Batal</Button>
                <Button variant="destructive" @click="deletePhoto">Ya, Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Modal Dialog Hapus QRIS -->
    <Dialog :open="confirmingQrisDeletion" @update:open="val => { if(!val) closeQrisModal(); }">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <div class="flex items-center gap-4 mb-2 text-destructive">
                    <div class="p-3 bg-destructive/10 rounded-full shrink-0">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <DialogTitle>Hapus QRIS?</DialogTitle>
                </div>
            </DialogHeader>
            
            <DialogDescription class="text-sm">
                Apakah Anda yakin ingin menghapus gambar QRIS ini? Tindakan ini tidak dapat dibatalkan dan penyewa tidak akan bisa memindai QRIS untuk pembayaran sampai Anda mengunggah yang baru.
            </DialogDescription>

            <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                <Button variant="outline" @click="closeQrisModal">Batal</Button>
                <Button variant="destructive" @click="deleteQris">Ya, Hapus QRIS</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
