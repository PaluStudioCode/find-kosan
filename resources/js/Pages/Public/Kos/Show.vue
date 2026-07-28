<script setup>
import { toast } from 'vue-sonner';
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
    Check,
} from 'lucide-vue-next';
// Note: ChevronLeft/Right used for lightbox, BedDouble for empty rooms state
import { ref, computed } from 'vue';
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
    const roomIdToBook = bookingRoom.value.available_room_id || bookingRoom.value.id;
    form.post(route('user.tenancies.store', roomIdToBook), {
        onSuccess: () => {
            bookingRoom.value = null;
        },
        onError: (err) => {
            if (err.start_date) toast.error(err.start_date);
            if (err.occupant_count) toast.error(err.occupant_count);
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
    reviewForm.post(route('user.kos.reviews.store', props.kos.id), {
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

const photoCategories = {
    'bangunan_depan': 'Tampak Depan',
    'dalam_kamar': 'Dalam Kamar',
    'kamar_mandi': 'Kamar Mandi',
    'fasilitas_umum': 'Fasilitas Umum',
    'lingkungan': 'Lingkungan',
    'lainnya': 'Lainnya'
};

// --- Computed ---
const kosPhotos = computed(() => props.kos.photos.map(p => ({ id: p.id, url: p.file_path, caption: p.caption, category: p.category })));
const availableRooms = computed(() => props.kos.rooms.filter(r => r.status === 'tersedia'));
const unavailableRooms = computed(() => props.kos.rooms.filter(r => r.status !== 'tersedia'));
const cheapestPrice = computed(() => {
    if (props.kos.rooms.length === 0) return null;
    return Math.min(...props.kos.rooms.map(r => r.price));
});

const groupedRooms = computed(() => {
    if (!props.kos.rooms) return [];
    
    const groups = {};
    props.kos.rooms.forEach(room => {
        const roomName = room.name || 'Kamar Standar';
        const key = `${roomName}-${room.price}-${room.capacity}`;
        
        if (!groups[key]) {
            groups[key] = {
                ...room,
                name: roomName,
                total_count: 1,
                available_count: room.status === 'tersedia' ? 1 : 0,
                available_room_id: room.status === 'tersedia' ? room.id : null,
            };
        } else {
            groups[key].total_count++;
            if (room.status === 'tersedia') {
                groups[key].available_count++;
                if (!groups[key].available_room_id) {
                    groups[key].available_room_id = room.id;
                }
            }
        }
    });
    
    return Object.values(groups);
});
</script>

<template>
    <PublicLayout>
        <Head :title="kos.name" />

        <!-- Hero Photo Gallery -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div class="mb-4">
                <Link :href="route('public.kos.index')" class="inline-flex items-center text-sm text-slate-500 hover:text-slate-900 transition-colors">
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
                        <span class="absolute bottom-3 left-3 bg-black/60 text-white text-xs px-2 py-1 rounded">{{ photoCategories[photo.category] || 'Lainnya' }}</span>
                    </div>
                </div>

                <!-- 3+ photos: Airbnb-style grid -->
                <div v-else class="grid grid-cols-4 grid-rows-2 gap-1 h-[280px] sm:h-[380px]">
                    <div class="col-span-2 row-span-2 cursor-pointer group relative overflow-hidden" @click="openLightbox(kosPhotos, 0)">
                        <img :src="kosPhotos[0].url" :alt="kosPhotos[0].caption || kos.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <span class="absolute bottom-3 left-3 bg-black/60 text-white text-xs px-2 py-1 rounded">{{ photoCategories[kosPhotos[0].category] || 'Lainnya' }}</span>
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
            <div v-else class="rounded-2xl bg-slate-100 h-[200px] flex items-center justify-center">
                <div class="text-center text-slate-400">
                    <ImageOff class="w-10 h-10 mx-auto mb-2" />
                    <p class="text-sm">Belum ada foto</p>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Info -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Title & Location -->
                    <div class="border-b border-slate-200 pb-6">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">{{ kos.name }}</h1>
                        <p class="text-slate-500 mt-2 flex items-start gap-1.5 text-sm font-medium">
                            <MapPin class="w-4 h-4 mt-0.5 shrink-0 text-teal-600" />
                            <span>{{ [kos.address, kos.subdistrict, kos.district, kos.city].filter(Boolean).join(', ') }}</span>
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="border-b border-slate-200 pb-6">
                        <h2 class="text-lg font-bold text-slate-900 mb-3">Tentang Kos Ini</h2>
                        <div class="text-slate-600 text-[15px] leading-relaxed whitespace-pre-wrap">{{ kos.description }}</div>
                    </div>

                    <!-- Facilities -->
                    <div v-if="kos.facilities.length > 0" :class="{'border-b border-slate-200 pb-6': kos.rules && kos.rules.length > 0}">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Fasilitas Umum</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div v-for="fac in kos.facilities" :key="fac.id" class="flex items-center gap-3 text-sm text-slate-700 font-medium">
                                <CheckCircle2 class="w-5 h-5 text-teal-500 shrink-0" />
                                <span>{{ fac.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rules -->
                    <div v-if="kos.rules && kos.rules.length > 0">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Peraturan Kos</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div v-for="rule in kos.rules" :key="rule.id" class="flex items-start gap-3 text-sm font-medium" :class="rule.is_positive ? 'text-teal-700' : 'text-red-600'">
                                <Check v-if="rule.is_positive" class="w-5 h-5 shrink-0 mt-0.5" />
                                <X v-else class="w-5 h-5 shrink-0 mt-0.5" />
                                <span class="leading-tight mt-0.5">{{ rule.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Sticky Summary Card -->
                <div>
                    <div class="sticky top-24 bg-white border border-slate-200 rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.08)] space-y-6">
                        <div v-if="cheapestPrice">
                            <p class="text-sm text-slate-500 font-medium">Harga mulai</p>
                            <p class="text-3xl font-extrabold text-slate-900 mt-1">{{ formatRupiah(cheapestPrice) }}<span class="text-base font-medium text-slate-500"> /bulan</span></p>
                        </div>
                        
                        <div class="flex items-center justify-between text-sm border-y border-slate-100 py-4">
                            <span class="text-slate-600 font-medium">Kamar tersedia</span>
                            <span class="font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">{{ availableRooms.length }} kamar</span>
                        </div>
                        
                        <div class="flex items-center justify-between text-sm pb-2">
                             <a href="#ulasan" class="flex items-center gap-1.5 text-slate-700 hover:text-teal-600 font-bold transition-colors">
                                 <Star class="w-5 h-5 fill-yellow-400 text-yellow-400" />
                                 {{ reviewSummary.average ?? 'Baru' }} <span class="font-medium text-slate-500">({{ reviewSummary.total }} ulasan)</span>
                             </a>
                        </div>
                        
                        <div class="space-y-3 pt-2">
                            <a href="#daftar-kamar" class="flex w-full justify-center items-center bg-teal-500 hover:bg-teal-600 text-white h-12 rounded-full font-bold transition-all hover:-translate-y-0.5 shadow-md">
                                Lihat Pilihan Kamar
                            </a>
                        </div>
                        
                        <!-- Report Button -->
                        <div class="pt-2 flex justify-center">
                            <Button
                                v-if="$page.props.auth.user && $page.props.auth.user.role === 'user'"
                                type="button"
                                variant="ghost"
                                class="text-xs text-slate-400 hover:text-slate-600 hover:bg-slate-50 h-auto py-1.5 px-3 rounded-full transition-colors"
                                @click="openReportDialog"
                            >
                                <Flag class="w-3 h-3 mr-1.5" />
                                Laporkan Kos Ini
                            </Button>
                            <Link v-else :href="route('login')" class="text-xs text-slate-400 hover:text-slate-600 hover:underline inline-flex items-center py-1.5 px-3">
                                <Flag class="w-3 h-3 mr-1.5" />
                                Login untuk Melapor
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rooms Section -->
            <div id="daftar-kamar" class="mt-12 scroll-mt-24 border-t border-slate-200 pt-8">
                <h2 class="text-2xl font-extrabold text-slate-900 mb-6">Pilihan Kamar</h2>

                <div v-if="groupedRooms.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div 
                        v-for="(group, idx) in groupedRooms" 
                        :key="idx" 
                        class="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-lg hover:border-teal-200 transition-all duration-300 flex flex-col"
                        :class="{ 'opacity-60': group.available_count === 0 }"
                    >
                        <!-- Header: Name + Status -->
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="min-w-0 pr-2">
                                <h3 class="font-bold text-lg text-slate-900 leading-tight truncate" :title="group.name">{{ group.name }}</h3>
                                <p class="text-sm text-slate-500 mt-1 flex items-center gap-1.5 font-medium">
                                    <Users class="w-4 h-4 text-slate-400" /> {{ group.capacity }} org
                                </p>
                            </div>
                            <span v-if="group.available_count === 0" class="shrink-0 bg-slate-100 text-slate-600 text-xs font-bold px-2.5 py-1 rounded-lg">
                                Penuh
                            </span>
                            <span v-else class="shrink-0 bg-teal-50 text-teal-700 text-xs font-bold px-2.5 py-1 rounded-lg text-center leading-tight">
                                {{ group.available_count }} Kamar<br>Tersedia
                            </span>
                        </div>

                        <!-- Price -->
                        <div class="mb-4">
                            <span class="text-2xl font-extrabold text-slate-900">{{ formatRupiah(group.price) }}</span>
                            <span class="text-sm font-medium text-slate-500 ml-1">/ {{ group.price_period }}</span>
                        </div>

                        <!-- Description -->
                        <p v-if="group.description" class="text-sm text-slate-600 mb-4 line-clamp-2 leading-relaxed">{{ group.description }}</p>

                        <!-- Room Facilities -->
                        <div v-if="group.facilities && group.facilities.length > 0" class="mb-6 flex flex-wrap gap-2">
                            <!-- Display first 3, then popover for rest to save space -->
                            <span v-for="fac in group.facilities.slice(0, 3)" :key="fac.id" class="inline-flex items-center gap-1 bg-slate-50 text-slate-600 text-[11px] font-semibold px-2.5 py-1 rounded-md border border-slate-100">
                                {{ fac.name }}
                            </span>
                            <Popover v-if="group.facilities.length > 3">
                                <PopoverTrigger as-child>
                                    <button type="button" class="inline-flex items-center gap-1 bg-slate-50 hover:bg-slate-100 text-teal-600 text-[11px] font-bold px-2.5 py-1 rounded-md border border-slate-100 transition-colors">
                                        +{{ group.facilities.length - 3 }} lainnya
                                    </button>
                                </PopoverTrigger>
                                <PopoverContent class="w-56 p-3 rounded-xl border-slate-100 shadow-xl" align="start">
                                    <p class="text-xs font-bold text-slate-900 mb-2">Fasilitas Kamar</p>
                                    <div class="flex flex-col gap-1.5">
                                        <div v-for="fac in group.facilities" :key="fac.id" class="flex items-center gap-1.5 text-xs text-slate-600 font-medium">
                                            <CheckCircle2 class="w-3 h-3 text-teal-500 shrink-0" />
                                            {{ fac.name }}
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <!-- Action (pushed to bottom) -->
                        <div class="mt-auto">
                            <template v-if="group.available_count > 0">
                                <template v-if="$page.props.auth.user && $page.props.auth.user.role === 'user'">
                                    <Button class="w-full rounded-full font-bold h-11 bg-slate-900 hover:bg-slate-800 text-white transition-all hover:-translate-y-0.5 shadow-md" @click="openBookModal(group)">
                                        Pesan Tipe Kamar Ini
                                    </Button>
                                </template>
                                <template v-else-if="!$page.props.auth.user">
                                    <Link :href="route('login')" class="block">
                                        <Button class="w-full rounded-full font-bold h-11 border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors shadow-sm" variant="outline">
                                            Login untuk Pesan
                                        </Button>
                                    </Link>
                                </template>
                            </template>
                            <template v-else>
                                <Button class="w-full rounded-full font-bold h-11 bg-slate-100 text-slate-400 hover:bg-slate-100 cursor-not-allowed" disabled>
                                    Tidak Tersedia
                                </Button>
                            </template>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <BedDouble class="w-10 h-10 text-slate-300 mx-auto mb-3" />
                    <p class="text-slate-500 font-medium">Belum ada kamar yang tersedia saat ini.</p>
                    <p class="text-slate-400 text-sm mt-1">Silakan kembali lagi nanti.</p>
                </div>
            </div>

            <!-- Rating & Reviews Section -->
            <section id="ulasan" class="mt-12 scroll-mt-20 border-t border-slate-200 pt-10">
                <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 text-xl font-bold text-slate-900">
                            <Star class="h-5 w-5 fill-yellow-400 text-yellow-400" />
                            Rating & Komentar
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ reviewSummary.total > 0
                                ? `${reviewSummary.average} dari 5 berdasarkan ${reviewSummary.total} ulasan`
                                : 'Belum ada ulasan untuk kos ini' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-1">
                        <form
                            v-if="$page.props.auth.user && $page.props.auth.user.role === 'user'"
                            class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            @submit.prevent="submitReview"
                        >
                            <div>
                                <h3 class="font-semibold text-slate-900">
                                    {{ currentReview ? 'Perbarui Ulasan Anda' : 'Beri Ulasan' }}
                                </h3>
                                <p class="mt-1 text-xs text-slate-500">Bagikan pengalaman Anda tentang kos ini.</p>
                            </div>

                            <div class="space-y-2">
                                <Label>Rating</Label>
                                <div class="flex gap-1" @mouseleave="hoverRating = 0">
                                    <button
                                        v-for="score in 5"
                                        :key="score"
                                        type="button"
                                        class="rounded p-0.5 transition-transform hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
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
                                                : 'text-slate-300'"
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
                                    <p class="ml-auto text-slate-400">{{ reviewForm.comment.length }}/1000</p>
                                </div>
                            </div>

                            <Button type="submit" class="w-full" :disabled="reviewForm.processing">
                                {{ reviewForm.processing
                                    ? 'Menyimpan...'
                                    : currentReview ? 'Perbarui Ulasan' : 'Kirim Ulasan' }}
                            </Button>
                        </form>

                        <div v-else class="rounded-2xl border border-dashed border-teal-200 bg-teal-50/50 p-6 text-center">
                            <LogIn class="mx-auto h-8 w-8 text-teal-500" />
                            <h3 class="mt-3 font-semibold text-slate-900">Punya pengalaman di kos ini?</h3>
                            <p class="mt-1 text-sm text-slate-500">Masuk sebagai penyewa untuk memberi rating dan komentar.</p>
                            <Link :href="route('login')" class="mt-4 inline-block">
                                <Button type="button">Masuk untuk Mengulas</Button>
                            </Link>
                        </div>
                    </div>

                    <div class="space-y-3 lg:col-span-2">
                        <article
                            v-for="review in reviews.data"
                            :key="review.id"
                            class="rounded-2xl border border-slate-200 bg-white p-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ review.user.name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ formatReviewDate(review.updated_at) }}</p>
                                </div>
                                <div class="flex" :aria-label="`${review.rating} dari 5 bintang`">
                                    <Star
                                        v-for="score in 5"
                                        :key="score"
                                        class="h-4 w-4"
                                        :class="score <= review.rating
                                            ? 'fill-yellow-400 text-yellow-400'
                                            : 'text-slate-200'"
                                    />
                                </div>
                            </div>
                            <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-600">{{ review.comment }}</p>
                        </article>

                        <div v-if="reviews.data.length === 0" class="rounded-2xl border border-dashed border-slate-200 py-12 text-center">
                            <MessageSquare class="mx-auto h-9 w-9 text-slate-300" />
                            <p class="mt-3 font-medium text-slate-500">Belum ada komentar.</p>
                            <p class="mt-1 text-sm text-slate-400">Jadilah penyewa pertama yang memberikan ulasan.</p>
                        </div>

                        <nav v-if="reviews.links && reviews.links.length > 3" class="flex flex-wrap justify-center gap-1 pt-3" aria-label="Navigasi ulasan">
                            <template v-for="(link, index) in reviews.links" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    preserve-scroll
                                    class="rounded-md border px-3 py-1.5 text-sm transition-colors"
                                    :class="link.active
                                        ? 'border-teal-600 bg-teal-600 text-white'
                                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="cursor-not-allowed rounded-md border border-slate-100 px-3 py-1.5 text-sm text-slate-300"
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
                    <span v-if="lightboxPhotos[lightboxIndex]?.category" class="ml-2 px-2 py-0.5 bg-white/20 text-white rounded text-xs">{{ photoCategories[lightboxPhotos[lightboxIndex].category] || 'Lainnya' }}</span>
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
                    <div v-if="bookingRoom" class="bg-slate-50 rounded-lg p-3">
                        <p class="font-semibold text-slate-900">{{ bookingRoom.name || `Kamar ${bookingRoom.room_number}` }}</p>
                        <p class="text-sm text-teal-600 font-medium">{{ formatRupiah(bookingRoom.price) }} / {{ bookingRoom.price_period }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="start_date">Mulai Sewa (Tanggal)</Label>
                        <Input id="start_date" type="date" v-model="form.start_date" required />
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="occupant_count">Jumlah Penghuni</Label>
                        <Input id="occupant_count" type="number" min="1" :max="bookingRoom?.capacity || 1" v-model="form.occupant_count" required />
                        <p class="text-xs text-slate-500">Maksimal {{ bookingRoom?.capacity }} orang</p>
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
                            <p class="ml-auto text-slate-400">{{ reportForm.description.length }}/1000</p>
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

