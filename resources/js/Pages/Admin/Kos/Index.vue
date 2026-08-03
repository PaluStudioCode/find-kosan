<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import { Head, Link, router } from '@inertiajs/vue3';
import { Deferred } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import StatusBadge from '@/components/StatusBadge.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Plus, MapPin, Building, Trash2, AlertTriangle, ExternalLink, Share2, Loader2 } from 'lucide-vue-next';

defineProps({
    boardingHouses: Object
});

const confirmingKosDeletion = ref(false);
const kosToDelete = ref(null);
const isDeleting = ref(false);

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
    
    isDeleting.value = true;
    router.delete(route('admin.kos.destroy', kosToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};

const copyLink = (kosId) => {
    const url = route('public.kos.show', kosId);
    const absoluteUrl = new URL(url, window.location.origin).href;
    
    if (navigator.share) {
        navigator.share({
            title: 'Lihat properti kos ini di FindKosan',
            url: absoluteUrl
        }).catch((err) => {
            if (err.name !== 'AbortError') {
                navigator.clipboard.writeText(absoluteUrl);
                toast.success('Tautan kos berhasil disalin');
            }
        });
    } else {
        navigator.clipboard.writeText(absoluteUrl);
        toast.success('Tautan kos berhasil disalin');
    }
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

        <Deferred data="boardingHouses">
            <template #fallback>
                <!-- Skeleton Loader (tampil secara instan berkat fitur Deferred Inertia) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <Card v-for="n in 6" :key="'skeleton-'+n" class="flex flex-col h-full overflow-hidden dark:bg-slate-900 dark:border-slate-800 animate-pulse border-slate-200">
                        <!-- Skeleton Image -->
                        <div class="w-full h-40 bg-slate-200 dark:bg-slate-800"></div>
                        
                        <CardHeader class="p-4 pb-2">
                            <div class="flex justify-between items-start gap-3">
                                <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
                                <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-16 shrink-0"></div>
                            </div>
                            <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-3/4 mt-3"></div>
                        </CardHeader>
                        
                        <CardContent class="p-4 py-2 flex-1">
                            <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-24 mb-3"></div>
                        </CardContent>
                        
                        <CardFooter class="p-4 pt-4 border-t dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex flex-nowrap items-center gap-2">
                            <div class="h-9 bg-slate-200 dark:bg-slate-700 rounded flex-1 min-w-0"></div>
                            <div class="flex items-center gap-2 shrink-0">
                                <div class="h-9 bg-slate-200 dark:bg-slate-700 rounded w-9"></div>
                                <div class="h-9 bg-slate-200 dark:bg-slate-700 rounded w-9"></div>
                            </div>
                        </CardFooter>
                    </Card>
                </div>
            </template>

            <!-- Actual Data -->
            <div v-if="boardingHouses.data.length > 0">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <Card v-for="kos in boardingHouses.data" :key="kos.id" class="flex flex-col h-full overflow-hidden hover:shadow-md transition-shadow dark:bg-slate-900 dark:border-slate-800">
                        <img v-if="kos.photos && kos.photos.length > 0" :src="kos.photos[0].file_path" class="w-full h-40 object-cover" />
                        <div v-else class="w-full h-40 bg-gray-100 dark:bg-slate-800 flex items-center justify-center">
                            <Building class="w-12 h-12 text-gray-300 dark:text-slate-600" />
                        </div>
                        
                        <CardHeader class="p-4 pb-2">
                            <div class="flex justify-between items-start gap-2">
                                <CardTitle class="text-base sm:text-lg line-clamp-1 dark:text-white" :title="kos.name">{{ kos.name }}</CardTitle>
                                <div class="shrink-0">
                                    <StatusBadge :status="kos.status" />
                                </div>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400 flex items-center mt-1" :title="kos.address + ', ' + kos.city">
                                <MapPin class="w-3 h-3 mr-1 shrink-0" /> <span class="truncate">{{ kos.address }}, {{ kos.city }}</span>
                            </p>
                        </CardHeader>
                        <CardContent class="p-4 py-2 flex-1">
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
                        <CardFooter class="p-4 pt-4 border-t dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/50 flex flex-nowrap items-center justify-between gap-2">
                            <Link :href="route('admin.kos.show', kos.id)" class="flex-1 min-w-0 flex items-center">
                                <Button variant="outline" size="sm" class="w-full h-9 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 dark:text-slate-300 dark:border-slate-700 px-2 sm:px-3 text-xs sm:text-sm truncate">Kelola</Button>
                            </Link>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <template v-if="kos.status === 'dipublikasikan'">
                                    <Button type="button" size="sm" variant="outline" class="h-9 px-2.5 sm:px-3 border-teal-200 text-teal-700 bg-teal-50 hover:bg-teal-100 dark:border-teal-900/50 dark:text-teal-400 dark:bg-teal-900/20 dark:hover:bg-teal-900/40" title="Bagikan Tautan Kos" @click="copyLink(kos.id)">
                                        <Share2 class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                    </Button>
                                    <a :href="route('public.kos.show', kos.id)" target="_blank" class="flex items-center">
                                        <Button type="button" size="sm" variant="outline" class="h-9 px-2.5 sm:px-3 border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:border-emerald-900/50 dark:text-emerald-400 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40" title="Lihat Halaman Publik">
                                            <ExternalLink class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                        </Button>
                                    </a>
                                </template>
                                <Button type="button" size="sm" variant="destructive" class="h-9 px-2.5 sm:px-3" @click="confirmKosDeletion(kos)" title="Hapus Kos" :disabled="kos.status === 'menunggu_verifikasi'">
                                    <Trash2 class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                                </Button>
                            </div>
                        </CardFooter>
                    </Card>
                </div>

                <!-- Pagination -->
                <div v-if="boardingHouses.links && boardingHouses.links.length > 3" class="mt-8 flex flex-wrap justify-center gap-1">
                    <template v-for="(link, k) in boardingHouses.links" :key="k">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm border rounded text-gray-400 bg-white dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500" v-html="link.label" />
                        <Link v-else :href="link.url" preserve-scroll class="px-3 py-1 text-sm border rounded transition-colors" :class="link.active ? 'bg-teal-600 text-white border-teal-600 pointer-events-none' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700'" v-html="link.label" />
                    </template>
                </div>
            </div>
            
            <EmptyState 
                v-else 
                title="Belum Ada Properti Kos" 
                description="Anda belum menambahkan properti kos apa pun. Klik tombol Tambah Kos untuk mulai menyewakan kos Anda." 
            />
        </Deferred>
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
                <Button variant="outline" @click="closeModal" class="w-full sm:w-auto dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" :disabled="isDeleting">Batal</Button>
                <Button variant="destructive" @click="deleteKos" class="w-full sm:w-auto" :disabled="isDeleting">
                    <Loader2 v-if="isDeleting" class="w-4 h-4 mr-2 animate-spin" />
                    {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Kos' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
