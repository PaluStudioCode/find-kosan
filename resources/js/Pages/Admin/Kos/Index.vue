<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import StatusBadge from '@/components/StatusBadge.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Plus, MapPin, Building, Trash2, AlertTriangle, ExternalLink } from 'lucide-vue-next';

defineProps({
    boardingHouses: Object
});

const confirmingKosDeletion = ref(false);
const kosToDelete = ref(null);

const confirmKosDeletion = (kos) => {
    kosToDelete.value = kos;
    confirmingKosDeletion.value = true;
};

const closeModal = () => {
    confirmingKosDeletion.value = false;
    setTimeout(() => {
        kosToDelete.value = null;
    }, 300);
};

const deleteKos = () => {
    if (!kosToDelete.value) return;
    
    router.delete(route('admin.kos.destroy', kosToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Kos" />

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Kos</h2>
                <p class="text-gray-500 dark:text-slate-400 mt-1">Kelola data properti kos, kamar, dan fasilitas.</p>
            </div>
            <Link :href="route('admin.kos.create')" class="w-full sm:w-auto">
                <Button class="w-full sm:w-auto">
                    <Plus class="w-4 h-4 mr-2" /> Tambah Kos
                </Button>
            </Link>
        </div>

        <div v-if="boardingHouses.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card v-for="kos in boardingHouses.data" :key="kos.id" class="overflow-hidden hover:shadow-md transition-shadow dark:bg-slate-900 dark:border-slate-800">
                <img v-if="kos.photos && kos.photos.length > 0" :src="kos.photos[0].file_path" class="w-full h-48 object-cover" />
                <div v-else class="w-full h-48 bg-gray-100 dark:bg-slate-800 flex items-center justify-center">
                    <Building class="w-12 h-12 text-gray-300 dark:text-slate-600" />
                </div>
                
                <CardHeader class="p-4 pb-2">
                    <div class="flex justify-between items-start">
                        <CardTitle class="text-lg line-clamp-1 dark:text-white">{{ kos.name }}</CardTitle>
                        <StatusBadge :status="kos.status" />
                    </div>
                    <p class="text-sm text-gray-500 dark:text-slate-400 flex items-center mt-1 line-clamp-1">
                        <MapPin class="w-3 h-3 mr-1 shrink-0" /> {{ kos.address }}, {{ kos.city }}
                    </p>
                </CardHeader>
                <CardContent class="p-4 py-2">
                    <div class="text-sm font-medium mb-3 bg-gray-50 dark:bg-slate-800 p-2 rounded border dark:border-slate-700 inline-flex items-center">
                        <Building class="w-4 h-4 mr-2 text-gray-400 dark:text-slate-400" />
                        <span class="dark:text-slate-200">{{ kos.rooms_count }}</span> <span class="text-gray-500 dark:text-slate-400 ml-1">Kamar</span>
                    </div>
                    
                    <div v-if="kos.status === 'menunggu_verifikasi'" class="text-xs text-orange-700 bg-orange-50 border border-orange-200 dark:bg-orange-900/30 dark:border-orange-900/50 dark:text-orange-400 p-2 rounded">
                        Sedang direview oleh Super Admin
                    </div>
                    <div v-if="kos.status === 'ditolak'" class="text-xs text-red-700 bg-red-50 border border-red-200 dark:bg-red-900/30 dark:border-red-900/50 dark:text-red-400 p-2 rounded">
                        <strong>Ditolak:</strong> {{ kos.verification_note }}
                    </div>
                    <div v-if="kos.pending_revisions" class="text-xs text-blue-700 bg-blue-50 border border-blue-200 dark:bg-blue-900/30 dark:border-blue-900/50 dark:text-blue-400 p-2 rounded mt-2">
                        Menunggu verifikasi revisi data
                    </div>
                </CardContent>
                <CardFooter class="p-4 pt-4 border-t dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/50 flex gap-2">
                    <Link :href="route('admin.kos.show', kos.id)" class="flex-1">
                        <Button variant="outline" class="w-full bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 dark:text-slate-300 dark:border-slate-700">Kelola Kos</Button>
                    </Link>
                    <a v-if="kos.status === 'dipublikasikan'" :href="route('public.kos.show', kos.id)" target="_blank">
                        <Button type="button" variant="outline" class="px-3 border-teal-200 text-teal-700 bg-teal-50 hover:bg-teal-100 dark:border-teal-900/50 dark:text-teal-400 dark:bg-teal-900/20 dark:hover:bg-teal-900/40" title="Lihat Halaman Publik">
                            <ExternalLink class="w-4 h-4" />
                        </Button>
                    </a>
                    <Button type="button" variant="destructive" class="px-3" @click="confirmKosDeletion(kos)" title="Hapus Kos" :disabled="kos.status === 'menunggu_verifikasi'">
                        <Trash2 class="w-4 h-4" />
                    </Button>
                </CardFooter>
            </Card>
        </div>
        
        <EmptyState 
            v-else 
            title="Belum Ada Properti Kos" 
            description="Anda belum menambahkan properti kos apa pun. Klik tombol Tambah Kos untuk mulai menyewakan kos Anda." 
        />
        
        <!-- Pagination -->
        <div v-if="boardingHouses.links && boardingHouses.links.length > 3" class="mt-8 flex flex-wrap justify-center gap-1">
            <template v-for="(link, k) in boardingHouses.links" :key="k">
                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm border rounded text-gray-400 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500" v-html="link.label" />
                <Link v-else :href="link.url" preserve-scroll class="px-3 py-1 text-sm border rounded transition-colors" :class="link.active ? 'bg-teal-600 text-white border-teal-600 pointer-events-none' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700'" v-html="link.label" />
            </template>
        </div>
    </AppLayout>

    <Dialog :open="confirmingKosDeletion" @update:open="val => { if(!val) closeModal(); }">
        <DialogContent class="sm:max-w-[425px] dark:bg-slate-900 dark:border-slate-800">
            <DialogHeader>
                <div class="flex items-center gap-4 mb-2 text-destructive dark:text-red-400">
                    <div class="p-3 bg-destructive/10 dark:bg-red-900/30 rounded-full shrink-0">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <DialogTitle class="dark:text-white">Hapus Properti Kos?</DialogTitle>
                </div>
            </DialogHeader>
            
            <DialogDescription class="text-sm dark:text-slate-400">
                Apakah Anda yakin ingin menghapus <strong class="text-foreground dark:text-white">{{ kosToDelete?.name }}</strong>? Semua data kamar, foto, dan transaksi terkait akan ikut terhapus atau disembunyikan. Tindakan ini tidak dapat dibatalkan.
            </DialogDescription>

            <DialogFooter class="mt-6 flex flex-col sm:flex-row justify-end gap-3 sm:gap-2">
                <Button variant="outline" @click="closeModal" class="w-full sm:w-auto dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Button>
                <Button variant="destructive" @click="deleteKos" class="w-full sm:w-auto">Ya, Hapus Kos</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
