<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import {
    MapPin,
    CheckCircle2,
    BedDouble,
    Users,
    ChevronLeft,
    ChevronRight,
    X,
    DoorOpen,
    ArrowLeft,
    ImageOff,
    Star,
    MessageSquare,
    Flag,
    LogIn,
} from 'lucide-vue-next';
// Note: ChevronLeft/Right used for lightbox, BedDouble for empty rooms state
import { ref, computed } from 'vue';
import { useToast } from '@/components/ui/toast/use-toast';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps({
    kos: {
        type: Object,
        required: true,
    },
    reviews: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    reviewSummary: {
        type: Object,
        default: () => ({ average: null, total: 0 }),
    },
    currentReview: {
        type: Object,
        default: null,
    },
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

// --- Rating & comments ---
const hoverRating = ref(0);
const reviewForm = useForm({
    rating: props.currentReview?.rating ?? 0,
    comment: props.currentReview?.comment ?? '',
});

const submitReview = () => {
    reviewForm.post(route('tenant.kos.reviews.store', props.kos.id), {
        preserveScroll: true,
        onSuccess: () => reviewForm.clearErrors(),
    });
};

// --- Report ---
const reportDialogOpen = ref(false);
const reportForm = useForm({
    boarding_house_id: props.kos.id,
    category: 'data_kos_tidak_valid',
    description: '',
});

const openReportDialog = () => {
    reportForm.clearErrors();
    reportDialogOpen.value = true;
};

const submitReport = () => {
    reportForm.post(route('reports.store'), {
        preserveScroll: true,
        onSuccess: () => {
            reportDialogOpen.value = false;
            reportForm.reset();
        },
    });
};

const formatReviewDate = (date) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(date));
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
                        <a href="#ulasan" class="flex items-center gap-1.5 px-3 py-2 bg-yellow-50 text-yellow-700 rounded-lg text-sm font-medium">
                            <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
                            {{ reviewSummary.average ?? 'Baru' }}
                            <span class="font-normal">({{ reviewSummary.total }} ulasan)</span>
                        </a>
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
                        <a href="#ulasan" class="flex w-full items-center justify-center gap-2 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 py-2.5 rounded-lg font-medium transition-colors text-sm">
                            <MessageSquare class="w-4 h-4" />
                            Lihat Rating & Komentar
                        </a>
                        <Button
                            v-if="$page.props.auth.user && $page.props.auth.user.role === 'penyewa'"
                            type="button"
                            variant="ghost"
                            class="w-full text-red-600 hover:bg-red-50 hover:text-red-700"
                            @click="openReportDialog"
                        >
                            <Flag class="w-4 h-4" />
                            Laporkan Kos Ini
                        </Button>
                        <Link v-else :href="route('login')" class="block">
                            <Button type="button" variant="ghost" class="w-full text-red-600 hover:bg-red-50 hover:text-red-700">
                                <Flag class="w-4 h-4" />
                                Login untuk Melapor
                            </Button>
                        </Link>
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

            <!-- Rating & Reviews Section -->
            <section id="ulasan" class="mt-12 scroll-mt-20 border-t border-gray-200 pt-10">
                <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 text-xl font-bold text-gray-900">
                            <Star class="h-5 w-5 fill-yellow-400 text-yellow-400" />
                            Rating & Komentar
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ reviewSummary.total > 0
                                ? `${reviewSummary.average} dari 5 berdasarkan ${reviewSummary.total} ulasan`
                                : 'Belum ada ulasan untuk kos ini' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-1">
                        <form
                            v-if="$page.props.auth.user && $page.props.auth.user.role === 'penyewa'"
                            class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                            @submit.prevent="submitReview"
                        >
                            <div>
                                <h3 class="font-semibold text-gray-900">
                                    {{ currentReview ? 'Perbarui Ulasan Anda' : 'Beri Ulasan' }}
                                </h3>
                                <p class="mt-1 text-xs text-gray-500">Bagikan pengalaman Anda tentang kos ini.</p>
                            </div>

                            <div class="space-y-2">
                                <Label>Rating</Label>
                                <div class="flex gap-1" @mouseleave="hoverRating = 0">
                                    <button
                                        v-for="score in 5"
                                        :key="score"
                                        type="button"
                                        class="rounded p-0.5 transition-transform hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                        :aria-label="`${score} bintang`"
                                        @mouseenter="hoverRating = score"
                                        @focus="hoverRating = score"
                                        @blur="hoverRating = 0"
                                        @click="reviewForm.rating = score"
                                    >
                                        <Star
                                            class="h-7 w-7 transition-colors"
                                            :class="score <= (hoverRating || reviewForm.rating)
                                                ? 'fill-yellow-400 text-yellow-400'
                                                : 'text-gray-300'"
                                        />
                                    </button>
                                </div>
                                <p v-if="reviewForm.errors.rating" class="text-xs text-red-600">{{ reviewForm.errors.rating }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="review-comment">Komentar</Label>
                                <Textarea
                                    id="review-comment"
                                    v-model="reviewForm.comment"
                                    rows="5"
                                    maxlength="1000"
                                    placeholder="Ceritakan pengalaman, fasilitas, lokasi, atau pelayanan kos..."
                                />
                                <div class="flex justify-between gap-3 text-xs">
                                    <p class="text-red-600">{{ reviewForm.errors.comment }}</p>
                                    <p class="ml-auto text-gray-400">{{ reviewForm.comment.length }}/1000</p>
                                </div>
                            </div>

                            <Button type="submit" class="w-full" :disabled="reviewForm.processing">
                                {{ reviewForm.processing
                                    ? 'Menyimpan...'
                                    : currentReview ? 'Perbarui Ulasan' : 'Kirim Ulasan' }}
                            </Button>
                        </form>

                        <div v-else class="rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/50 p-6 text-center">
                            <LogIn class="mx-auto h-8 w-8 text-indigo-500" />
                            <h3 class="mt-3 font-semibold text-gray-900">Punya pengalaman di kos ini?</h3>
                            <p class="mt-1 text-sm text-gray-500">Masuk sebagai penyewa untuk memberi rating dan komentar.</p>
                            <Link :href="route('login')" class="mt-4 inline-block">
                                <Button type="button">Masuk untuk Mengulas</Button>
                            </Link>
                        </div>
                    </div>

                    <div class="space-y-3 lg:col-span-2">
                        <article
                            v-for="review in reviews.data"
                            :key="review.id"
                            class="rounded-2xl border border-gray-200 bg-white p-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ review.user.name }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ formatReviewDate(review.updated_at) }}</p>
                                </div>
                                <div class="flex" :aria-label="`${review.rating} dari 5 bintang`">
                                    <Star
                                        v-for="score in 5"
                                        :key="score"
                                        class="h-4 w-4"
                                        :class="score <= review.rating
                                            ? 'fill-yellow-400 text-yellow-400'
                                            : 'text-gray-200'"
                                    />
                                </div>
                            </div>
                            <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-600">{{ review.comment }}</p>
                        </article>

                        <div v-if="reviews.data.length === 0" class="rounded-2xl border border-dashed border-gray-200 py-12 text-center">
                            <MessageSquare class="mx-auto h-9 w-9 text-gray-300" />
                            <p class="mt-3 font-medium text-gray-500">Belum ada komentar.</p>
                            <p class="mt-1 text-sm text-gray-400">Jadilah penyewa pertama yang memberikan ulasan.</p>
                        </div>

                        <nav v-if="reviews.links && reviews.links.length > 3" class="flex flex-wrap justify-center gap-1 pt-3" aria-label="Navigasi ulasan">
                            <template v-for="(link, index) in reviews.links" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    preserve-scroll
                                    class="rounded-md border px-3 py-1.5 text-sm transition-colors"
                                    :class="link.active
                                        ? 'border-indigo-600 bg-indigo-600 text-white'
                                        : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="cursor-not-allowed rounded-md border border-gray-100 px-3 py-1.5 text-sm text-gray-300"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </div>
            </section>
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

        <!-- Report Dialog -->
        <Dialog v-model:open="reportDialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Laporkan {{ kos.name }}</DialogTitle>
                    <DialogDescription>
                        Laporan akan dikirim ke admin untuk ditinjau. Jelaskan masalah dengan jelas dan objektif.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitReport">
                    <div class="space-y-2">
                        <Label for="report-category">Kategori Laporan</Label>
                        <Select v-model="reportForm.category">
                            <SelectTrigger id="report-category">
                                <SelectValue placeholder="Pilih kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="data_kos_tidak_valid">Data Kos Tidak Valid</SelectItem>
                                <SelectItem value="kontak_tidak_valid">Kontak Tidak Valid</SelectItem>
                                <SelectItem value="foto_tidak_sesuai">Foto Tidak Sesuai</SelectItem>
                                <SelectItem value="lainnya">Lainnya</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="reportForm.errors.category" class="text-xs text-red-600">{{ reportForm.errors.category }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="report-description">Deskripsi Masalah</Label>
                        <Textarea
                            id="report-description"
                            v-model="reportForm.description"
                            rows="6"
                            maxlength="1000"
                            placeholder="Contoh: Titik lokasi pada peta tidak sesuai dengan alamat kos..."
                        />
                        <div class="flex justify-between gap-3 text-xs">
                            <p class="text-red-600">{{ reportForm.errors.description }}</p>
                            <p class="ml-auto text-gray-400">{{ reportForm.description.length }}/1000</p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="reportDialogOpen = false">Batal</Button>
                        <Button type="submit" :disabled="reportForm.processing">
                            {{ reportForm.processing ? 'Mengirim...' : 'Kirim Laporan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </PublicLayout>
</template>
