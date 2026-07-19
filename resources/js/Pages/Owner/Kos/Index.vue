<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Plus, MapPin, Building, Trash2, AlertTriangle } from 'lucide-vue-next';

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
    
    router.delete(route('owner.kos.destroy', kosToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Kos" />

        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Manajemen Kos</h2>
                <p class="text-gray-500 mt-1">Kelola data properti kos, kamar, dan fasilitas.</p>
            </div>
            <Link :href="route('owner.kos.create')">
                <Button>
                    <Plus class="w-4 h-4 mr-2" /> Tambah Kos
                </Button>
            </Link>
        </div>

        <div v-if="boardingHouses.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Card v-for="kos in boardingHouses.data" :key="kos.id" class="overflow-hidden hover:shadow-md transition-shadow">
                <img v-if="kos.photos && kos.photos.length > 0" :src="kos.photos[0].file_path" class="w-full h-48 object-cover" />
                <div v-else class="w-full h-48 bg-gray-100 flex items-center justify-center">
                    <Building class="w-12 h-12 text-gray-300" />
                </div>
                
                <CardHeader class="p-4 pb-2">
                    <div class="flex justify-between items-start">
                        <CardTitle class="text-lg line-clamp-1">{{ kos.name }}</CardTitle>
                        <StatusBadge :status="kos.status" />
                    </div>
                    <p class="text-sm text-gray-500 flex items-center mt-1 line-clamp-1">
                        <MapPin class="w-3 h-3 mr-1 shrink-0" /> {{ kos.address }}, {{ kos.city }}
                    </p>
                </CardHeader>
                <CardContent class="p-4 py-2">
                    <div class="text-sm font-medium mb-3 bg-gray-50 p-2 rounded border inline-flex items-center">
                        <Building class="w-4 h-4 mr-2 text-gray-400" />
                        <span>{{ kos.rooms_count }}</span> <span class="text-gray-500 ml-1">Kamar</span>
                    </div>
                    
                    <div v-if="kos.status === 'menunggu_verifikasi'" class="text-xs text-orange-700 bg-orange-50 border border-orange-200 p-2 rounded">
                        Sedang direview oleh Super Admin
                    </div>
                    <div v-if="kos.status === 'ditolak'" class="text-xs text-red-700 bg-red-50 border border-red-200 p-2 rounded">
                        <strong>Ditolak:</strong> {{ kos.verification_note }}
                    </div>
                    <div v-if="kos.pending_revisions" class="text-xs text-blue-700 bg-blue-50 border border-blue-200 p-2 rounded mt-2">
                        Menunggu verifikasi revisi data
                    </div>
                </CardContent>
                <CardFooter class="p-4 pt-4 border-t bg-gray-50/50 flex gap-2">
                    <Link :href="route('owner.kos.show', kos.id)" class="flex-1">
                        <Button variant="outline" class="w-full bg-white hover:bg-gray-50">Kelola Kos</Button>
                    </Link>
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
        <div v-if="boardingHouses.links && boardingHouses.links.length > 3" class="mt-8 flex justify-center gap-1">
            <template v-for="(link, k) in boardingHouses.links" :key="k">
                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm border rounded text-gray-400 bg-white" v-html="link.label" />
                <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-gray-100 bg-white" :class="{ 'bg-gray-900 text-white hover:bg-gray-800 border-gray-900': link.active }" v-html="link.label" />
            </template>
        </div>
    </AppLayout>

    <Dialog :open="confirmingKosDeletion" @update:open="val => { if(!val) closeModal(); }">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <div class="flex items-center gap-4 mb-2 text-destructive">
                    <div class="p-3 bg-destructive/10 rounded-full shrink-0">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <DialogTitle>Hapus Properti Kos?</DialogTitle>
                </div>
            </DialogHeader>
            
            <DialogDescription class="text-sm">
                Apakah Anda yakin ingin menghapus <strong class="text-foreground">{{ kosToDelete?.name }}</strong>? Semua data kamar, foto, dan transaksi terkait akan ikut terhapus atau disembunyikan. Tindakan ini tidak dapat dibatalkan.
            </DialogDescription>

            <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                <Button variant="outline" @click="closeModal">Batal</Button>
                <Button variant="destructive" @click="deleteKos">Ya, Hapus Kos</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
