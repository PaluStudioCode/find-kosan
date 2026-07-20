<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import { MapPin, CheckCircle2, BedDouble, Users, ChevronLeft, ChevronRight, X, DoorOpen, ArrowLeft, ImageOff } from 'lucide-vue-next';
// Note: ChevronLeft/Right used for lightbox, BedDouble for empty rooms state
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/use-toast';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

const props = defineProps({
    kos: Object,
});

const { toast } = useToast();

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
};

// --- Photo Lightbox ---
const lightboxOpen = ref(false);
const lightboxPhotos = ref([]);
const lightboxIndex = ref(0);

const openLightbox = (photos, index = 0) => {
    lightboxPhotos.value = photos;
    lightboxIndex.value = index;
    lightboxOpen.value = true;
};

const nextPhoto = () => {
    lightboxIndex.value = (lightboxIndex.value + 1) % lightboxPhotos.value.length;
};
const prevPhoto = () => {
    lightboxIndex.value = (lightboxIndex.value - 1 + lightboxPhotos.value.length) % lightboxPhotos.value.length;
};



// --- Booking ---
const bookingRoom = ref(null);
const form = useForm({
    start_date: '',
    occupant_count: 1
});

const openBookModal = (room) => {
    bookingRoom.value = room;
    form.start_date = '';
    form.occupant_count = 1;
};

const submitBooking = () => {
    form.post(route('tenant.tenancies.store', bookingRoom.value.id), {
        onSuccess: () => {
            bookingRoom.value = null;
        },
        onError: (err) => {
            if (err.start_date) toast({ title: 'Gagal', description: err.start_date, variant: 'destructive' });
            if (err.occupant_count) toast({ title: 'Gagal', description: err.occupant_count, variant: 'destructive' });
        }
    });
};

// --- Computed ---
const kosPhotos = computed(() => props.kos.photos.map(p => ({ id: p.id, url: p.file_path, caption: p.caption })));
const availableRooms = computed(() => props.kos.rooms.filter(r => r.status === 'tersedia'));
const unavailableRooms = computed(() => props.kos.rooms.filter(r => r.status !== 'tersedia'));
const cheapestPrice = computed(() => {
    if (props.kos.rooms.length === 0) return null;
    return Math.min(...props.kos.rooms.map(r => r.price));
});
</script>

<template>
    <PublicLayout>
        <Head :title="kos.name" />

        <!-- Hero Photo Gallery -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div class="mb-4">
                <Link :href="route('public.kos.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-900 transition-colors">
                    <ArrowLeft class="w-4 h-4 mr-1" /> Kembali ke Pencarian
                </Link>
            </div>

            <div v-if="kosPhotos.length > 0" class="relative rounded-2xl overflow-hidden">
                <!-- Single photo -->
                <div v-if="kosPhotos.length === 1" class="cursor-pointer" @click="openLightbox(kosPhotos, 0)">
                    <img :src="kosPhotos[0].url" :alt="kosPhotos[0].caption || kos.name" class="w-full h-[320px] sm:h-[400px] object-cover" />
                </div>

                <!-- 2 photos -->
                <div v-else-if="kosPhotos.length === 2" class="grid grid-cols-2 gap-1 h-[320px] sm:h-[400px]">
                    <div v-for="(photo, i) in kosPhotos.slice(0, 2)" :key="photo.id" class="cursor-pointer group relative overflow-hidden" @click="openLightbox(kosPhotos, i)">
                        <img :src="photo.url" :alt="photo.caption || kos.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    </div>
                </div>

                <!-- 3+ photos: Airbnb-style grid -->
                <div v-else class="grid grid-cols-4 grid-rows-2 gap-1 h-[280px] sm:h-[380px]">
                    <div class="col-span-2 row-span-2 cursor-pointer group relative overflow-hidden" @click="openLightbox(kosPhotos, 0)">
                        <img :src="kosPhotos[0].url" :alt="kosPhotos[0].caption || kos.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div class="cursor-pointer group relative overflow-hidden" @click="openLightbox(kosPhotos, 1)">
                        <img :src="kosPhotos[1].url" :alt="kosPhotos[1].caption" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div class="cursor-pointer group relative overflow-hidden" @click="openLightbox(kosPhotos, 2)">
                        <img :src="kosPhotos[2].url" :alt="kosPhotos[2].caption" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div v-if="kosPhotos.length >= 4" class="cursor-pointer group relative overflow-hidden" @click="openLightbox(kosPhotos, 3)">
                        <img :src="kosPhotos[3].url" :alt="kosPhotos[3].caption" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    </div>
                    <div v-if="kosPhotos.length >= 5" class="cursor-pointer group relative overflow-hidden" @click="openLightbox(kosPhotos, 4)">
                        <img :src="kosPhotos[4].url" :alt="kosPhotos[4].caption" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <!-- Overlay if more photos -->
                        <div v-if="kosPhotos.length > 5" class="absolute inset-0 bg-black/50 flex items-center justify-center">
                            <span class="text-white font-semibold text-lg">+{{ kosPhotos.length - 5 }} Foto</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No photo placeholder -->
            <div v-else class="rounded-2xl bg-gray-100 h-[200px] flex items-center justify-center">
                <div class="text-center text-gray-400">
                    <ImageOff class="w-10 h-10 mx-auto mb-2" />
                    <p class="text-sm">Belum ada foto</p>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Title & Location -->
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">{{ kos.name }}</h1>
                        <p class="text-gray-500 mt-1.5 flex items-start gap-1.5 text-sm">
                            <MapPin class="w-4 h-4 mt-0.5 shrink-0" />
                            <span>{{ [kos.address, kos.subdistrict, kos.district, kos.city].filter(Boolean).join(', ') }}</span>
                        </p>
                    </div>

                    <!-- Quick Stats -->
                    <div class="flex flex-wrap gap-3">
                        <div class="flex items-center gap-2 px-3 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium">
                            <DoorOpen class="w-4 h-4" />
                            {{ kos.rooms.length }} Kamar
                        </div>
                        <div v-if="availableRooms.length > 0" class="flex items-center gap-2 px-3 py-2 bg-emerald-50 text-emerald-700 rounded-lg text-sm font-medium">
                            <CheckCircle2 class="w-4 h-4" />
                            {{ availableRooms.length }} Tersedia
                        </div>
                        <div v-if="cheapestPrice" class="flex items-center gap-2 px-3 py-2 bg-amber-50 text-amber-700 rounded-lg text-sm font-medium">
                            Mulai {{ formatRupiah(cheapestPrice) }}
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Tentang Kos Ini</h2>
                        <div class="text-gray-600 text-[15px] leading-relaxed whitespace-pre-wrap">{{ kos.description }}</div>
                    </div>

                    <!-- Facilities -->
                    <div v-if="kos.facilities.length > 0">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Fasilitas Umum</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <div v-for="fac in kos.facilities" :key="fac.id" class="flex items-center gap-2 text-sm text-gray-600 py-1.5">
                                <CheckCircle2 class="w-4 h-4 text-indigo-500 shrink-0" />
                                <span>{{ fac.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Sticky Summary Card -->
                <div>
                    <div class="sticky top-20 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm space-y-4">
                        <div v-if="cheapestPrice">
                            <p class="text-sm text-gray-500">Harga mulai</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatRupiah(cheapestPrice) }}<span class="text-sm font-normal text-gray-500"> /bulan</span></p>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t pt-3">
                            <span class="text-gray-500">Kamar tersedia</span>
                            <span class="font-semibold text-emerald-600">{{ availableRooms.length }} dari {{ kos.rooms.length }}</span>
                        </div>
                        <a href="#daftar-kamar" class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition-colors text-sm">
                            Lihat Pilihan Kamar
                        </a>
                        <div v-if="!$page.props.auth.user" class="text-center text-xs text-gray-400">
                            <Link :href="route('login')" class="text-indigo-600 hover:underline">Masuk</Link> untuk memesan kamar
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rooms Section -->
            <div id="daftar-kamar" class="mt-10 scroll-mt-20">
                <h2 class="text-xl font-bold text-gray-900 mb-5">Pilihan Kamar</h2>

                <div v-if="kos.rooms.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div 
                        v-for="room in kos.rooms" 
                        :key="room.id" 
                        class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md hover:border-indigo-200 transition-all duration-200 flex flex-col"
                        :class="{ 'opacity-50': room.status !== 'tersedia' }"
                    >
                        <!-- Header: Name + Status -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div>
                                <h3 class="font-semibold text-gray-900 leading-tight">{{ room.name || `Kamar ${room.room_number}` }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                    No. {{ room.room_number }} · <Users class="w-3 h-3" /> {{ room.capacity }} orang
                                </p>
                            </div>
                            <span v-if="room.status !== 'tersedia'" class="shrink-0 bg-red-100 text-red-600 text-xs font-medium px-2 py-0.5 rounded-full">
                                Penuh
                            </span>
                            <span v-else class="shrink-0 bg-emerald-100 text-emerald-600 text-xs font-medium px-2 py-0.5 rounded-full">
                                Tersedia
                            </span>
                        </div>

                        <!-- Price -->
                        <div class="mb-3">
                            <span class="text-xl font-bold text-indigo-600">{{ formatRupiah(room.price) }}</span>
                            <span class="text-xs text-gray-400 ml-0.5">/ {{ room.price_period }}</span>
                        </div>

                        <!-- Description -->
                        <p v-if="room.description" class="text-sm text-gray-500 mb-3 line-clamp-2">{{ room.description }}</p>

                        <!-- Room Facilities -->
                        <div v-if="room.facilities.length > 0" class="mb-4">
                            <Popover>
                                <PopoverTrigger as-child>
                                    <button type="button" class="text-xs text-indigo-600 font-medium hover:text-indigo-700 transition-colors">
                                        Lihat {{ room.facilities.length }} Fasilitas
                                    </button>
                                </PopoverTrigger>
                                <PopoverContent class="w-56 p-3" align="start">
                                    <p class="text-xs font-semibold text-gray-900 mb-2">Fasilitas Kamar</p>
                                    <div class="flex flex-col gap-1.5">
                                        <div v-for="fac in room.facilities" :key="fac.id" class="flex items-center gap-1.5 text-xs text-gray-600">
                                            <CheckCircle2 class="w-3 h-3 text-indigo-500 shrink-0" />
                                            {{ fac.name }}
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <!-- Action (pushed to bottom) -->
                        <div class="mt-auto">
                            <template v-if="room.status === 'tersedia'">
                                <template v-if="$page.props.auth.user && $page.props.auth.user.role === 'penyewa'">
                                    <Button class="w-full" size="sm" @click="openBookModal(room)">
                                        Pesan Kamar Ini
                                    </Button>
                                </template>
                                <template v-else-if="!$page.props.auth.user">
                                    <Link :href="route('login')" class="block">
                                        <Button class="w-full" size="sm" variant="outline">
                                            Login untuk Pesan
                                        </Button>
                                    </Link>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                    <BedDouble class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 font-medium">Belum ada kamar yang tersedia saat ini.</p>
                    <p class="text-gray-400 text-sm mt-1">Silakan kembali lagi nanti.</p>
                </div>
            </div>
        </div>

        <!-- Lightbox Dialog -->
        <Dialog v-model:open="lightboxOpen">
            <DialogContent class="max-w-4xl bg-black/95 p-0 border-none shadow-none flex flex-col justify-center h-screen sm:h-auto sm:max-h-[90vh] sm:rounded-lg overflow-hidden [&>button]:hidden">
                <div class="absolute top-4 right-4 z-50">
                    <button class="w-10 h-10 flex items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80 border border-white/20 transition-colors shadow-lg" @click="lightboxOpen = false">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="relative flex items-center justify-center flex-1 min-h-0">
                    <button v-if="lightboxPhotos.length > 1" class="absolute left-4 w-11 h-11 flex items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80 border border-white/20 z-50 transition-colors shadow-lg" @click="prevPhoto">
                        <ChevronLeft class="w-6 h-6" />
                    </button>
                    
                    <img v-if="lightboxPhotos.length > 0" :src="lightboxPhotos[lightboxIndex].url" :alt="lightboxPhotos[lightboxIndex].caption" class="max-w-full max-h-[80vh] object-contain" />
                    
                    <button v-if="lightboxPhotos.length > 1" class="absolute right-4 w-11 h-11 flex items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80 border border-white/20 z-50 transition-colors shadow-lg" @click="nextPhoto">
                        <ChevronRight class="w-6 h-6" />
                    </button>
                </div>
                
                <div class="text-center py-3 text-white/60 text-sm">
                    {{ lightboxIndex + 1 }} / {{ lightboxPhotos.length }}
                    <span v-if="lightboxPhotos[lightboxIndex]?.caption" class="ml-2">— {{ lightboxPhotos[lightboxIndex].caption }}</span>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Booking Dialog -->
        <Dialog :open="!!bookingRoom" @update:open="(val) => { if(!val) bookingRoom = null }">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Pesan Kamar</DialogTitle>
                </DialogHeader>
                
                <form @submit.prevent="submitBooking" class="space-y-4">
                    <div v-if="bookingRoom" class="bg-gray-50 rounded-lg p-3">
                        <p class="font-semibold text-gray-900">{{ bookingRoom.name || `Kamar ${bookingRoom.room_number}` }}</p>
                        <p class="text-sm text-indigo-600 font-medium">{{ formatRupiah(bookingRoom.price) }} / {{ bookingRoom.price_period }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="start_date">Mulai Sewa (Tanggal)</Label>
                        <Input id="start_date" type="date" v-model="form.start_date" required />
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="occupant_count">Jumlah Penghuni</Label>
                        <Input id="occupant_count" type="number" min="1" :max="bookingRoom?.capacity || 1" v-model="form.occupant_count" required />
                        <p class="text-xs text-gray-500">Maksimal {{ bookingRoom?.capacity }} orang</p>
                    </div>

                    <DialogFooter class="mt-6">
                        <Button type="button" variant="ghost" @click="bookingRoom = null">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Ajukan Sewa</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </PublicLayout>
</template>
