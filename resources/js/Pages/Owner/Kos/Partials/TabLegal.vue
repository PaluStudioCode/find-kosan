<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { useToast } from '@/components/ui/toast/use-toast';
import { FileText, Trash2, UploadCloud, AlertCircle, AlertTriangle } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';

const props = defineProps({
    kos: Object,
    isLocked: Boolean
});

const { toast } = useToast();

const form = useForm({
    document_type: 'identitas_pemilik_pengelola',
    file: null,
});

const uploadDocument = () => {
    form.post(route('owner.kos.legal-documents.store', props.kos.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Notifikasi sukses ditangani oleh layout global (flash.success)
            form.reset('file');
        },
        onError: (err) => {
            if (err.file) toast({ title: 'Gagal', description: err.file, variant: 'destructive' });
        }
    });
};

const confirmingDocDeletion = ref(false);
const docToDelete = ref(null);

const confirmDocDeletion = (docId) => {
    docToDelete.value = docId;
    confirmingDocDeletion.value = true;
};

const closeDocModal = () => {
    confirmingDocDeletion.value = false;
    setTimeout(() => {
        docToDelete.value = null;
    }, 300);
};

const deleteDocument = () => {
    if (!docToDelete.value) return;

    router.delete(route('owner.kos.legal-documents.destroy', { kos: props.kos.id, legalDocument: docToDelete.value }), {
        preserveScroll: true,
        onSuccess: () => {
            // Notifikasi sukses ditangani oleh layout global (flash.success)
            closeDocModal();
        },
        onError: () => {
            toast({ title: 'Gagal', description: 'Gagal menghapus dokumen.', variant: 'destructive' });
            closeDocModal();
        }
    });
};

const docTypes = [
    { value: 'identitas_pemilik_pengelola', label: 'Identitas Pemilik (KTP dll)' },
    { value: 'bukti_kepemilikan_pengelolaan', label: 'Bukti Kepemilikan' },
    { value: 'surat_kuasa_pengelolaan', label: 'Surat Kuasa' },
    { value: 'izin_usaha', label: 'Izin Usaha' },
    { value: 'dokumen_lainnya', label: 'Lainnya' }
];
</script>

<template>
    <div class="space-y-8">
        <div v-if="isLocked" class="bg-orange-50 border border-orange-200 text-orange-800 p-4 rounded-lg flex items-start mb-6">
            <div class="mr-3 mt-0.5 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            </div>
            <div>
                <h4 class="font-semibold">Data Sedang Ditinjau</h4>
                <p class="text-sm mt-1">Anda tidak dapat menambah atau menghapus dokumen legalitas selama proses peninjauan oleh Super Admin berlangsung.</p>
            </div>
        </div>

        <fieldset :disabled="isLocked" :class="{ 'opacity-60': isLocked }">
            <div>
                <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg flex items-start mb-6">
                    <AlertCircle class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" />
                    <div>
                        <h4 class="font-semibold">Dokumen Legalitas Terproteksi</h4>
                        <p class="text-sm mt-1">Dokumen yang Anda unggah di sini bersifat rahasia dan <strong>tidak akan pernah</strong> ditampilkan di halaman publik kos Anda. Dokumen hanya digunakan oleh Super Admin untuk keperluan verifikasi legalitas properti.</p>
                    </div>
                </div>

            <h3 class="text-lg font-semibold border-b pb-2 mb-4">Unggah Dokumen Baru</h3>
            <form @submit.prevent="uploadDocument" class="bg-gray-50 p-4 rounded border grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="space-y-2">
                    <Label for="doc_type">Jenis Dokumen</Label>
                    <select id="doc_type" v-model="form.document_type" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <option v-for="t in docTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <Label for="doc_file">File (PDF, JPG, PNG. Maks 5MB)</Label>
                    <Input id="doc_file" type="file" accept=".pdf,image/*" @change="e => form.file = e.target.files[0]" required />
                </div>

                <Button type="submit" :disabled="form.processing || !form.file" class="w-full">
                    <UploadCloud class="w-4 h-4 mr-2" /> Unggah Dokumen
                </Button>
            </form>
        </div>

        <div>
            <h3 class="text-lg font-semibold border-b pb-2 mb-4">Daftar Dokumen Anda</h3>
            
            <div v-if="!kos.legal_documents || kos.legal_documents.length === 0" class="text-center py-8 text-gray-500 border rounded bg-gray-50 border-dashed">
                Belum ada dokumen legalitas. Minimal 1 dokumen diperlukan sebelum mengajukan verifikasi.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="doc in kos.legal_documents" :key="doc.id" class="border rounded-lg p-4 flex justify-between items-center bg-white shadow-sm hover:shadow-md transition">
                    <div class="flex items-center space-x-3">
                        <div class="bg-gray-100 p-2 rounded text-gray-500">
                            <FileText class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ doc.document_type }}</p>
                            <p class="text-xs text-gray-500">Tersimpan secara rahasia</p>
                        </div>
                    </div>
                    <Button variant="outline" size="sm" class="text-red-500 hover:text-red-700 hover:bg-red-50" @click="confirmDocDeletion(doc.id)" :disabled="isLocked">
                        <Trash2 class="w-4 h-4 mr-1" /> Hapus
                    </Button>
                </div>
            </div>
        </div>
        </fieldset>
    </div>

    <!-- Modal Dialog Hapus Dokumen -->
    <Dialog :open="confirmingDocDeletion" @update:open="val => { if(!val) closeDocModal(); }">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <div class="flex items-center gap-4 mb-2 text-destructive">
                    <div class="p-3 bg-destructive/10 rounded-full shrink-0">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <DialogTitle>Hapus Dokumen?</DialogTitle>
                </div>
            </DialogHeader>
            
            <DialogDescription class="text-sm">
                Apakah Anda yakin ingin menghapus dokumen legalitas ini? Dokumen yang terhapus tidak dapat dikembalikan.
            </DialogDescription>

            <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                <Button variant="outline" @click="closeDocModal">Batal</Button>
                <Button variant="destructive" @click="deleteDocument">Ya, Hapus Dokumen</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
