<script setup>
import {
    computed,
    defineAsyncComponent,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
} from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import {
    ArrowRight,
    BadgeCheck,
    BarChart3,
    BellRing,
    Building2,
    Check,
    CheckCheck,
    ChevronDown,
    ClipboardCheck,
    CreditCard,
    DoorOpen,
    FileCheck2,
    Flag,
    HelpCircle,
    History,
    Home,
    KeyRound,
    LocateFixed,
    Map,
    MapPin,
    MessageCircle,
    Quote,
    ReceiptText,
    Search,
    ShieldCheck,
    Star,
    SlidersHorizontal,
    UploadCloud,
    UsersRound,
    WalletCards,
} from 'lucide-vue-next';

const LandingHeroScene = defineAsyncComponent(
    () => import('@/Components/LandingHeroScene.vue'),
);
const LandingMapDiscovery = defineAsyncComponent(
    () => import('@/Components/LandingMapDiscovery.vue'),
);

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    stats: {
        type: Object,
        default: () => ({
            publishedKos: 0,
            availableRooms: 0,
            activeTenants: 0,
            registeredOwners: 0,
        }),
    },
    featuredReviews: {
        type: Array,
        default: () => [],
    },
    mapKos: {
        type: Array,
        default: () => [],
    },
});

const landingPage = ref(null);
const displayedStats = reactive({
    publishedKos: 0,
    availableRooms: 0,
    activeTenants: 0,
    registeredOwners: 0,
});

const statisticCards = computed(() => [
    {
        key: 'publishedKos',
        label: 'Kos dipublikasikan',
        value: displayedStats.publishedKos,
        icon: Building2,
        tone: 'bg-teal-50 text-teal-700',
    },
    {
        key: 'availableRooms',
        label: 'Kamar tersedia',
        value: displayedStats.availableRooms,
        icon: DoorOpen,
        tone: 'bg-orange-50 text-orange-700',
    },
    {
        key: 'activeTenants',
        label: 'Penyewa aktif',
        value: displayedStats.activeTenants,
        icon: UsersRound,
        tone: 'bg-blue-50 text-blue-700',
    },
    {
        key: 'registeredOwners',
        label: 'Pemilik aktif',
        value: displayedStats.registeredOwners,
        icon: BadgeCheck,
        tone: 'bg-violet-50 text-violet-700',
    },
]);

const formatNumber = (number) => new Intl.NumberFormat('id-ID').format(number ?? 0);

const formatDate = (date) => new Intl.DateTimeFormat('id-ID', {
    month: 'short',
    year: 'numeric',
}).format(new Date(date));

const initials = (name) => name
    ?.split(' ')
    .slice(0, 2)
    .map((word) => word.charAt(0))
    .join('')
    .toUpperCase() || 'P';

const faqs = [
    {
        question: 'Bagaimana cara mencari kos melalui peta?',
        answer: 'Buka halaman Cari Kos, izinkan akses lokasi jika diperlukan, lalu geser peta atau pilih marker untuk melihat ringkasan kos. Dari sana Anda dapat membuka detail kamar, fasilitas, harga, rating, dan komentar.',
    },
    {
        question: 'Apakah saya harus memiliki akun untuk melihat kos?',
        answer: 'Tidak. Daftar kos, peta, detail kamar, fasilitas, rating, dan komentar dapat dilihat sebagai guest. Akun penyewa diperlukan saat mengajukan sewa, memberi ulasan, atau membuat laporan.',
    },
    {
        question: 'Bagaimana proses pengajuan sewa kamar?',
        answer: 'Pilih kamar yang masih tersedia, tentukan tanggal mulai dan jumlah penghuni, kemudian kirim pengajuan. Setelah tercatat, status sewa dan invoice dapat dipantau dari akun penyewa.',
    },
    {
        question: 'Metode pembayaran apa yang tersedia?',
        answer: 'Pembayaran dapat diproses melalui gateway Duitku atau unggah bukti pembayaran sesuai pengaturan kos. Detail instruksi dan status konfirmasi akan tampil pada invoice.',
    },
    {
        question: 'Bagaimana jika data atau titik lokasi kos tidak sesuai?',
        answer: 'Gunakan tombol Laporkan Kos pada halaman detail. Pilih kategori masalah dan berikan keterangan yang jelas agar admin dapat meninjau serta menindaklanjuti laporan.',
    },
    {
        question: 'Apa saja yang dapat dikelola oleh pemilik kos?',
        answer: 'Pemilik dapat mengelola profil kos, titik lokasi, kamar, harga, fasilitas, foto, dokumen legal, penyewa, pembayaran, wallet, dan pengajuan penarikan dana dari satu dashboard.',
    },
];

let revealObserver;
let statisticAnimationFrame;
let statisticsAnimated = false;

const assignFinalStatistics = () => {
    Object.keys(displayedStats).forEach((key) => {
        displayedStats[key] = props.stats[key] ?? 0;
    });
};

const animateStatistics = () => {
    if (statisticsAnimated) return;
    statisticsAnimated = true;

    const duration = 1100;
    const startedAt = performance.now();

    const update = (timestamp) => {
        const progress = Math.min((timestamp - startedAt) / duration, 1);
        const easedProgress = 1 - Math.pow(1 - progress, 3);

        Object.keys(displayedStats).forEach((key) => {
            displayedStats[key] = Math.round((props.stats[key] ?? 0) * easedProgress);
        });

        if (progress < 1) {
            statisticAnimationFrame = window.requestAnimationFrame(update);
        } else {
            assignFinalStatistics();
        }
    };

    statisticAnimationFrame = window.requestAnimationFrame(update);
};

onMounted(() => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reducedMotion || !('IntersectionObserver' in window)) {
        assignFinalStatistics();
        return;
    }

    landingPage.value?.classList.add('reveal-enabled');

    revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            entry.target.classList.add('is-revealed');

            if (entry.target.hasAttribute('data-counter-trigger')) {
                animateStatistics();
            }

            revealObserver.unobserve(entry.target);
        });
    }, {
        threshold: 0.14,
        rootMargin: '0px 0px -8% 0px',
    });

    landingPage.value
        ?.querySelectorAll('[data-reveal]')
        .forEach((element) => revealObserver.observe(element));
});

onBeforeUnmount(() => {
    revealObserver?.disconnect();
    window.cancelAnimationFrame(statisticAnimationFrame);
});
</script>

<template>
    <PublicLayout landing hide-footer>
        <Head title="Cari dan Kelola Kos dalam Satu Platform">
            <meta
                head-key="description"
                name="description"
                content="Temukan kos melalui peta, ajukan sewa, kelola pembayaran, dan pantau masa sewa dalam satu platform."
            />
        </Head>

        <div ref="landingPage">
        <!-- 1. Three.js Hero -->
        <section class="relative isolate overflow-hidden bg-[#071a1d] text-white">
            <div class="absolute inset-0 -z-10">
                <div class="absolute -left-40 top-12 h-[420px] w-[420px] rounded-full bg-teal-500/15 blur-[110px]" />
                <div class="absolute -right-32 bottom-0 h-[520px] w-[520px] rounded-full bg-orange-400/10 blur-[130px]" />
                <div class="hero-grid absolute inset-0 opacity-35" />
            </div>

            <div class="mx-auto grid min-h-[calc(100vh-4rem)] max-w-7xl grid-cols-1 items-center gap-8 px-4 pb-24 pt-14 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:gap-4 lg:px-8 lg:pb-28 lg:pt-10">
                <div class="relative z-10 max-w-2xl">
                    <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-teal-200/15 bg-white/[0.06] px-3.5 py-2 text-xs font-semibold tracking-wide text-teal-100 backdrop-blur-md">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal-300 opacity-70" />
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-teal-300" />
                        </span>
                        Ekosistem kos terintegrasi
                    </div>

                    <h1 class="max-w-xl text-4xl font-bold leading-[1.06] tracking-[-0.045em] text-white sm:text-6xl lg:text-[4.35rem]">
                        Temukan ruang untuk
                        <span class="text-[#77e2c3]">hidup lebih dekat.</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">
                        Jelajahi kos melalui peta, periksa kamar dan ulasan, lalu kelola sewa serta pembayaran dari satu tempat yang transparan.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <Link :href="route('public.kos.index')">
                            <Button class="h-12 w-full rounded-full bg-[#f7924a] px-6 text-sm font-semibold text-[#172225] shadow-[0_14px_34px_-12px_rgba(247,146,74,.8)] hover:bg-[#ffa866] sm:w-auto">
                                Jelajahi Kos
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                        <a href="#cara-kerja">
                            <Button variant="outline" class="h-12 w-full rounded-full border-white/15 bg-white/[0.04] px-6 text-sm font-semibold text-white hover:bg-white/10 hover:text-white sm:w-auto">
                                Lihat Cara Kerja
                            </Button>
                        </a>
                    </div>

                    <div class="mt-9 flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-300">
                        <span class="flex items-center gap-2">
                            <Check class="h-4 w-4 text-teal-300" />
                            Pencarian berbasis peta
                        </span>
                        <span class="flex items-center gap-2">
                            <Check class="h-4 w-4 text-teal-300" />
                            Data ditinjau admin
                        </span>
                    </div>
                </div>

                <div class="relative min-h-[460px] lg:min-h-[650px]">
                    <LandingHeroScene />

                    <div class="absolute bottom-10 left-2 rounded-2xl border border-white/10 bg-[#0c292b]/85 p-4 shadow-2xl backdrop-blur-xl sm:left-8 lg:bottom-20">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-300/10">
                                <MapPin class="h-5 w-5 text-teal-300" />
                            </div>
                            <div>
                                <p class="text-xs text-slate-400">Siap dijelajahi</p>
                                <p class="font-semibold text-white">{{ formatNumber(stats.publishedKos) }} kos dipublikasikan</p>
                            </div>
                        </div>
                    </div>

                    <div class="absolute right-2 top-14 rounded-2xl border border-white/10 bg-white/[0.07] px-4 py-3 shadow-2xl backdrop-blur-xl sm:right-8 lg:top-24">
                        <div class="flex items-center gap-2 text-sm font-medium">
                            <span class="h-2 w-2 rounded-full bg-[#f7924a]" />
                            {{ formatNumber(stats.availableRooms) }} kamar tersedia
                        </div>
                    </div>
                </div>
            </div>

            <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-white to-transparent" />
        </section>

        <!-- 3. Platform statistics -->
        <section id="statistics" aria-labelledby="statistics-heading" class="bg-white py-12 sm:py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div data-reveal class="mb-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <h2 id="statistics-heading" class="text-base font-bold text-slate-900">Platform dalam angka</h2>
                    <p class="text-xs text-slate-400">Diperbarui berdasarkan data aktif di sistem</p>
                </div>

                <div data-reveal data-counter-trigger class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <article
                        v-for="(stat, index) in statisticCards"
                        :key="stat.key"
                        data-reveal="scale"
                        class="stat-card"
                        :style="{ '--reveal-delay': `${index * 90}ms` }"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="stat.tone">
                            <component :is="stat.icon" class="h-4.5 w-4.5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold leading-none tracking-[-0.035em] text-slate-950">
                                {{ formatNumber(stat.value) }}
                            </p>
                            <p class="mt-1.5 truncate text-xs font-medium text-slate-500">{{ stat.label }}</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- 4. Animated map discovery -->
        <section id="peta-kos" class="scroll-mt-20 overflow-hidden bg-[#f3f7f5] py-20 sm:py-24">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-[0.78fr_1.22fr] lg:px-8">
                <div data-reveal="left">
                    <p class="section-eyebrow">Pencarian berbasis lokasi</p>
                    <h2 class="section-title mt-4">Atur radius. Temukan titik kos di area tujuan.</h2>
                    <p class="section-copy mt-5">
                        Peta membantu Anda melihat persebaran kos secara visual. Tentukan lokasi pusat, sesuaikan radius, lalu pilih marker untuk memeriksa kos yang berada dalam jangkauan.
                    </p>

                    <div class="mt-8 space-y-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-teal-700 shadow-sm">
                                <LocateFixed class="h-4 w-4" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Lokasi sebagai titik pusat</h3>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Gunakan posisi Anda untuk memulai pencarian kos sekitar.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-teal-700 shadow-sm">
                                <SlidersHorizontal class="h-4 w-4" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Radius yang dapat disesuaikan</h3>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Persempit atau perluas area sesuai jarak yang Anda inginkan.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-teal-700 shadow-sm">
                                <Map class="h-4 w-4" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Marker menuju detail kos</h3>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Pilih titik untuk melihat informasi kos sebelum membuka detail lengkap.</p>
                            </div>
                        </div>
                    </div>

                    <Link :href="route('public.kos.index')" class="mt-8 inline-flex">
                        <Button class="h-11 rounded-full bg-[#0c292b] px-5 text-sm font-bold text-white hover:bg-[#143b3e]">
                            Buka Peta Kos
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </Link>
                </div>

                <div data-reveal="right">
                    <LandingMapDiscovery :items="mapKos" />
                    <p class="mt-3 text-center text-[10px] text-slate-400">
                        Pratinjau interaktif menggunakan data kos berkoordinat yang sudah dipublikasikan.
                    </p>
                </div>
            </div>
        </section>

        <!-- 6. How it works -->
        <section id="cara-kerja" class="scroll-mt-20 bg-white py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="section-eyebrow">Dari pencarian sampai pindah</p>
                    <h2 class="section-title mt-4">Empat langkah menuju kos yang tepat.</h2>
                    <p class="section-copy mt-5">Alur yang jelas membantu Anda mengambil keputusan tanpa berpindah-pindah aplikasi.</p>
                </div>

                <div class="relative mt-16 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                    <div class="absolute left-[12.5%] right-[12.5%] top-9 hidden border-t border-dashed border-teal-300 lg:block" />

                    <article data-reveal="scale" class="step-card group" style="--reveal-delay: 0ms">
                        <div class="step-icon">
                            <Search class="h-5 w-5" />
                            <span class="step-number">01</span>
                        </div>
                        <h3 class="mt-6 text-lg font-bold text-slate-900">Cari dari peta</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Jelajahi area dan pilih marker kos yang sesuai dengan lokasi tujuan Anda.</p>
                    </article>

                    <article data-reveal="scale" class="step-card group" style="--reveal-delay: 90ms">
                        <div class="step-icon">
                            <MapPin class="h-5 w-5" />
                            <span class="step-number">02</span>
                        </div>
                        <h3 class="mt-6 text-lg font-bold text-slate-900">Periksa detail</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Bandingkan kamar, harga, fasilitas, foto, rating, dan komentar penyewa.</p>
                    </article>

                    <article data-reveal="scale" class="step-card group" style="--reveal-delay: 180ms">
                        <div class="step-icon">
                            <ClipboardCheck class="h-5 w-5" />
                            <span class="step-number">03</span>
                        </div>
                        <h3 class="mt-6 text-lg font-bold text-slate-900">Ajukan sewa</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Pilih kamar tersedia, tanggal mulai, dan jumlah penghuni untuk mengirim pengajuan.</p>
                    </article>

                    <article data-reveal="scale" class="step-card group" style="--reveal-delay: 270ms">
                        <div class="step-icon">
                            <KeyRound class="h-5 w-5" />
                            <span class="step-number">04</span>
                        </div>
                        <h3 class="mt-6 text-lg font-bold text-slate-900">Kelola masa sewa</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Pantau invoice, pembayaran, dan status masa sewa melalui dashboard penyewa.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- 7. Security & transparency -->
        <section id="keamanan" class="scroll-mt-20 bg-[#f3f7f5] py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-14 lg:grid-cols-[0.85fr_1.15fr]">
                    <div data-reveal="left">
                        <p class="section-eyebrow">Keamanan & transparansi</p>
                        <h2 class="section-title mt-4">Informasi yang lebih jelas sebelum Anda memilih.</h2>
                        <p class="section-copy mt-5">
                            Platform menyediakan lapisan pemeriksaan dan umpan balik agar pencarian kos dapat dilakukan dengan pertimbangan yang lebih baik.
                        </p>
                        <Link :href="route('public.kos.index')" class="mt-7 inline-flex items-center gap-2 text-sm font-bold text-teal-700 hover:text-teal-900">
                            Periksa kos yang tersedia
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>

                    <div data-reveal="right" class="grid gap-4 sm:grid-cols-2">
                        <article class="rounded-3xl bg-[#0c292b] p-7 text-white sm:row-span-2">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-300/10 text-teal-300">
                                <ShieldCheck class="h-6 w-6" />
                            </div>
                            <h3 class="mt-8 text-xl font-bold">Verifikasi oleh admin</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-300">Data properti dan dokumen legal dapat diajukan pemilik untuk ditinjau sebelum kos dipublikasikan.</p>

                            <div class="mt-8 space-y-3 border-t border-white/10 pt-6">
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-300/10"><Check class="h-3.5 w-3.5 text-teal-300" /></span>
                                    Data kos dan lokasi
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-300/10"><Check class="h-3.5 w-3.5 text-teal-300" /></span>
                                    Foto dan fasilitas
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-300/10"><Check class="h-3.5 w-3.5 text-teal-300" /></span>
                                    Dokumen legal
                                </div>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-slate-200 bg-white p-6">
                            <Star class="h-6 w-6 fill-[#f7924a] text-[#f7924a]" />
                            <h3 class="mt-5 font-bold text-slate-900">Rating dan komentar</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Baca pengalaman penyewa lain atau perbarui ulasan Anda pada halaman detail kos.</p>
                        </article>

                        <article class="rounded-3xl border border-slate-200 bg-white p-6">
                            <Flag class="h-6 w-6 text-rose-500" />
                            <h3 class="mt-5 font-bold text-slate-900">Pelaporan terarah</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Laporkan data, kontak, foto, atau titik lokasi yang tidak sesuai untuk ditinjau admin.</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Tenancy and payment -->
        <section class="overflow-hidden bg-white py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-14 lg:grid-cols-2">
                    <div data-reveal="left" class="order-2 lg:order-1">
                        <div class="relative rounded-[2rem] bg-[#eaf3f0] p-4 sm:p-7">
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_24px_60px_-30px_rgba(15,45,46,.35)] sm:p-6">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.15em] text-slate-400">Ringkasan sewa</p>
                                        <p class="mt-1 font-bold text-slate-900">Invoice bulanan</p>
                                    </div>
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Menunggu pembayaran</span>
                                </div>

                                <div class="mt-5 grid grid-cols-2 gap-3">
                                    <div class="rounded-xl bg-slate-50 p-4">
                                        <ReceiptText class="h-5 w-5 text-teal-700" />
                                        <p class="mt-4 text-xs text-slate-400">Periode</p>
                                        <p class="mt-1 text-sm font-bold text-slate-800">Bulanan</p>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 p-4">
                                        <History class="h-5 w-5 text-blue-600" />
                                        <p class="mt-4 text-xs text-slate-400">Status sewa</p>
                                        <p class="mt-1 text-sm font-bold text-slate-800">Aktif</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between rounded-xl bg-[#0c292b] p-4 text-white">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10">
                                            <CreditCard class="h-4 w-4 text-teal-200" />
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-400">Metode pembayaran</p>
                                            <p class="text-sm font-semibold">Duitku / bukti transfer</p>
                                        </div>
                                    </div>
                                    <ArrowRight class="h-4 w-4 text-teal-200" />
                                </div>
                            </div>

                            <div class="absolute -bottom-6 -right-3 hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:block">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50">
                                        <CheckCheck class="h-4 w-4 text-emerald-600" />
                                    </span>
                                    <div>
                                        <p class="text-xs text-slate-400">Riwayat tersimpan</p>
                                        <p class="text-sm font-bold text-slate-900">Status mudah dipantau</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div data-reveal="right" class="order-1 lg:order-2">
                        <p class="section-eyebrow">Sewa & pembayaran</p>
                        <h2 class="section-title mt-4">Administrasi sewa yang tidak membuat bingung.</h2>
                        <p class="section-copy mt-5">
                            Penyewa dan pemilik melihat alur yang sama: invoice tercatat, bukti pembayaran tersimpan, dan status dapat diperiksa kembali.
                        </p>

                        <div class="mt-8 grid gap-5 sm:grid-cols-2">
                            <div class="feature-line">
                                <ReceiptText class="h-5 w-5 text-teal-700" />
                                <div>
                                    <h3 class="font-bold text-slate-900">Invoice terstruktur</h3>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">Periode, jatuh tempo, dan nominal tercatat pada akun.</p>
                                </div>
                            </div>
                            <div class="feature-line">
                                <UploadCloud class="h-5 w-5 text-teal-700" />
                                <div>
                                    <h3 class="font-bold text-slate-900">Bukti pembayaran</h3>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">Unggah bukti bila metode pembayaran memerlukannya.</p>
                                </div>
                            </div>
                            <div class="feature-line">
                                <CreditCard class="h-5 w-5 text-teal-700" />
                                <div>
                                    <h3 class="font-bold text-slate-900">Gateway Duitku</h3>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">Pilihan pembayaran digital terintegrasi pada invoice.</p>
                                </div>
                            </div>
                            <div class="feature-line">
                                <History class="h-5 w-5 text-teal-700" />
                                <div>
                                    <h3 class="font-bold text-slate-900">Riwayat transparan</h3>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">Pantau konfirmasi pembayaran dan perjalanan masa sewa.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. WhatsApp notifications -->
        <section class="bg-[#0c292b] py-24 text-white sm:py-32">
            <div class="mx-auto grid max-w-7xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div data-reveal="left">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-300">Notifikasi WhatsApp</p>
                    <h2 class="mt-4 max-w-xl text-3xl font-bold tracking-[-0.035em] sm:text-5xl">Informasi penting hadir di kanal yang familiar.</h2>
                    <p class="mt-5 max-w-xl text-base leading-7 text-slate-300">
                        Sistem menyiapkan pemberitahuan terkait pemesanan, pembayaran, konfirmasi, dan pengingat invoice agar tindak lanjut tidak terlewat.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <span class="notification-chip"><BellRing class="h-4 w-4" /> Pengajuan sewa</span>
                        <span class="notification-chip"><ReceiptText class="h-4 w-4" /> Pengingat invoice</span>
                        <span class="notification-chip"><CheckCheck class="h-4 w-4" /> Status pembayaran</span>
                    </div>
                </div>

                <div data-reveal="right" class="relative mx-auto w-full max-w-lg">
                    <div class="absolute -inset-10 rounded-full bg-teal-300/10 blur-3xl" />
                    <div class="relative rounded-[2rem] border border-white/10 bg-white/[0.06] p-4 backdrop-blur-xl sm:p-6">
                        <div class="mb-5 flex items-center gap-3 border-b border-white/10 pb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#34c88a]">
                                <MessageCircle class="h-5 w-5 text-[#0c292b]" />
                            </div>
                            <div>
                                <p class="text-sm font-bold">Kos Online</p>
                                <p class="text-xs text-teal-200">Notifikasi sistem</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="max-w-[88%] rounded-2xl rounded-tl-sm bg-white p-4 text-[#183334] shadow-lg">
                                <p class="text-sm font-semibold">Pengingat pembayaran</p>
                                <p class="mt-1 text-xs leading-5 text-slate-600">Invoice masa sewa Anda mendekati tanggal jatuh tempo. Silakan periksa detail pada dashboard.</p>
                                <p class="mt-2 text-right text-[10px] text-slate-400">09.15</p>
                            </div>
                            <div class="ml-auto max-w-[82%] rounded-2xl rounded-tr-sm bg-[#d8f7e9] p-4 text-[#183334] shadow-lg">
                                <p class="text-xs leading-5">Pembayaran sudah saya kirim melalui halaman invoice.</p>
                                <p class="mt-2 flex items-center justify-end gap-1 text-[10px] text-teal-700">09.18 <CheckCheck class="h-3 w-3" /></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 10. Owner solution -->
        <section id="pemilik" class="scroll-mt-20 bg-[#f7f8f4] py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-14 lg:grid-cols-[0.85fr_1.15fr]">
                    <div data-reveal="left">
                        <p class="section-eyebrow">Untuk pemilik kos</p>
                        <h2 class="section-title mt-4">Operasional properti dalam satu kendali.</h2>
                        <p class="section-copy mt-5">Mulai dari mempublikasikan kos hingga memantau pembayaran dan saldo, semua dikelola melalui dashboard yang sama.</p>

                        <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')" class="mt-8 inline-block">
                            <Button class="h-12 rounded-full bg-[#0c292b] px-6 text-sm font-bold text-white hover:bg-[#143b3e]">
                                Daftarkan Kos Anda
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                        <Link v-else-if="$page.props.auth.user?.role === 'pemilik_kos'" :href="route('owner.dashboard')" class="mt-8 inline-block">
                            <Button class="h-12 rounded-full bg-[#0c292b] px-6 text-sm font-bold text-white hover:bg-[#143b3e]">
                                Buka Dashboard Pemilik
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>

                    <div data-reveal="right" class="grid gap-4 sm:grid-cols-2">
                        <article class="owner-card">
                            <Building2 class="h-6 w-6 text-teal-700" />
                            <h3>Properti & kamar</h3>
                            <p>Kelola profil kos, lokasi, foto, fasilitas, kamar, kapasitas, dan harga.</p>
                        </article>
                        <article class="owner-card">
                            <FileCheck2 class="h-6 w-6 text-teal-700" />
                            <h3>Verifikasi dokumen</h3>
                            <p>Unggah dokumen legal dan ajukan pemeriksaan kos kepada admin.</p>
                        </article>
                        <article class="owner-card">
                            <BarChart3 class="h-6 w-6 text-teal-700" />
                            <h3>Sewa & pembayaran</h3>
                            <p>Pantau penyewa, invoice, bukti bayar, dan proses konfirmasi.</p>
                        </article>
                        <article class="owner-card owner-card--wallet">
                            <WalletCards class="h-6 w-6 text-orange-800" />
                            <h3>Wallet & penarikan</h3>
                            <p>Periksa saldo pendapatan dan ajukan penarikan dana secara tercatat.</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- 11. Tenant reviews -->
        <section id="ulasan" class="scroll-mt-20 bg-white py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div data-reveal class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div class="max-w-2xl">
                        <p class="section-eyebrow">Suara penyewa</p>
                        <h2 class="section-title mt-4">Pengalaman nyata membantu pilihan berikutnya.</h2>
                    </div>
                    <Link :href="route('public.kos.index')" class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-teal-700 hover:text-teal-900">
                        Jelajahi semua kos
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>

                <div v-if="featuredReviews.length" class="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="(review, index) in featuredReviews"
                        :key="review.id"
                        data-reveal="scale"
                        class="review-card"
                        :style="{ '--reveal-delay': `${(index % 3) * 90}ms` }"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex gap-0.5" :aria-label="`${review.rating} dari 5 bintang`">
                                <Star
                                    v-for="score in 5"
                                    :key="score"
                                    class="h-4 w-4"
                                    :class="score <= review.rating ? 'fill-[#f7924a] text-[#f7924a]' : 'text-slate-200'"
                                />
                            </div>
                            <Quote class="h-7 w-7 text-teal-100" />
                        </div>

                        <p class="mt-6 line-clamp-4 text-sm leading-7 text-slate-600">“{{ review.comment }}”</p>

                        <div class="mt-7 flex items-center gap-3 border-t border-slate-100 pt-5">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#dff2eb] text-xs font-bold text-teal-800">
                                {{ initials(review.user?.name) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900">{{ review.user?.name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ review.boarding_house?.name }} · {{ formatDate(review.created_at) }}</p>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="mt-14 rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-6 py-14 text-center">
                    <MessageCircle class="mx-auto h-9 w-9 text-slate-300" />
                    <h3 class="mt-4 font-bold text-slate-800">Belum ada ulasan yang dipublikasikan</h3>
                    <p class="mt-2 text-sm text-slate-500">Ulasan penyewa akan muncul di sini setelah mereka membagikan pengalaman pada detail kos.</p>
                </div>
            </div>
        </section>

        <!-- 12. FAQ -->
        <section class="bg-[#f3f7f5] py-24 sm:py-32">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[0.7fr_1.3fr] lg:px-8">
                <div data-reveal="left">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-100 text-teal-800">
                        <HelpCircle class="h-5 w-5" />
                    </div>
                    <p class="section-eyebrow mt-6">Pertanyaan umum</p>
                    <h2 class="section-title mt-4">Hal penting sebelum memulai.</h2>
                    <p class="section-copy mt-5">Temukan jawaban singkat mengenai pencarian, penyewaan, pembayaran, laporan, dan pengelolaan kos.</p>
                </div>

                <div data-reveal="right" class="divide-y divide-slate-200 border-y border-slate-200">
                    <details v-for="faq in faqs" :key="faq.question" class="faq-item group">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-5 py-6 font-bold text-slate-900">
                            {{ faq.question }}
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-teal-800 transition-transform group-open:rotate-180">
                                <ChevronDown class="h-4 w-4" />
                            </span>
                        </summary>
                        <p class="max-w-2xl pb-6 pr-12 text-sm leading-7 text-slate-500">{{ faq.answer }}</p>
                    </details>
                </div>
            </div>
        </section>

        <!-- 13. Final CTA -->
        <section class="bg-white px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
            <div data-reveal="scale" class="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-[#0c292b] px-6 py-14 text-center text-white sm:px-12 sm:py-20">
                <div class="absolute -left-16 -top-20 h-64 w-64 rounded-full border border-teal-200/10" />
                <div class="absolute -left-5 -top-10 h-40 w-40 rounded-full border border-teal-200/10" />
                <div class="absolute -bottom-32 -right-16 h-80 w-80 rounded-full bg-[#f7924a]/10 blur-2xl" />

                <div class="relative mx-auto max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-300">Mulai dari sini</p>
                    <h2 class="mt-4 text-3xl font-bold tracking-[-0.04em] sm:text-5xl">Kos yang tepat atau operasional yang lebih rapi—keduanya dimulai hari ini.</h2>
                    <p class="mx-auto mt-5 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">Pilih jalur Anda dan gunakan fitur yang memang dirancang untuk kebutuhan penyewa maupun pemilik kos.</p>

                    <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                        <Link :href="route('public.kos.index')">
                            <Button class="h-12 w-full rounded-full bg-[#f7924a] px-6 font-bold text-[#172225] hover:bg-[#ffa866] sm:w-auto">
                                Mulai Cari Kos
                                <Search class="h-4 w-4" />
                            </Button>
                        </Link>
                        <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')">
                            <Button variant="outline" class="h-12 w-full rounded-full border-white/15 bg-white/[0.04] px-6 font-bold text-white hover:bg-white/10 hover:text-white sm:w-auto">
                                Daftarkan Kos Anda
                                <Building2 class="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- 14. Professional footer -->
        <footer class="border-t border-slate-800 bg-[#071a1d] text-white">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_0.8fr_0.8fr_1fr]">
                    <div>
                        <Link :href="route('home')" class="inline-flex items-center gap-2 text-lg font-bold">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-300 text-[#071a1d]">
                                <Home class="h-4 w-4" />
                            </span>
                            Kos Online
                        </Link>
                        <p class="mt-5 max-w-sm text-sm leading-7 text-slate-400">Platform pencarian dan pengelolaan kos yang mempertemukan kebutuhan penyewa dan pemilik dalam alur yang lebih tertata.</p>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold">Jelajahi</h3>
                        <nav class="mt-5 flex flex-col gap-3 text-sm text-slate-400">
                            <Link :href="route('public.kos.index')" class="hover:text-teal-300">Cari Kos</Link>
                            <a href="#cara-kerja" class="hover:text-teal-300">Cara Kerja</a>
                            <a href="#ulasan" class="hover:text-teal-300">Ulasan Penyewa</a>
                        </nav>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold">Platform</h3>
                        <nav class="mt-5 flex flex-col gap-3 text-sm text-slate-400">
                            <a href="#keamanan" class="hover:text-teal-300">Keamanan</a>
                            <a href="#pemilik" class="hover:text-teal-300">Untuk Pemilik</a>
                            <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')" class="hover:text-teal-300">Masuk Akun</Link>
                        </nav>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold">Akses cepat</h3>
                        <p class="mt-5 text-sm leading-6 text-slate-400">Temukan kos yang tersedia di sekitar area tujuan Anda.</p>
                        <Link :href="route('public.kos.index')" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-teal-300 hover:text-teal-200">
                            Buka peta kos
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>

                <div class="mt-12 flex flex-col gap-3 border-t border-white/10 pt-7 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ new Date().getFullYear() }} Kos Online. Seluruh hak dilindungi.</p>
                    <p>Dibangun untuk ekosistem sewa kos yang lebih tertata.</p>
                </div>
            </div>
        </footer>
        </div>
    </PublicLayout>
</template>

<style scoped>
.hero-grid {
    background-image:
        linear-gradient(rgba(119, 226, 195, 0.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(119, 226, 195, 0.06) 1px, transparent 1px);
    background-size: 56px 56px;
    mask-image: linear-gradient(to bottom, black, transparent 92%);
}

.section-eyebrow {
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: #0f766e;
}

.section-title {
    max-width: 46rem;
    font-size: clamp(2rem, 4vw, 3.35rem);
    line-height: 1.08;
    letter-spacing: -0.04em;
    font-weight: 750;
    color: #0f172a;
}

.section-copy {
    max-width: 40rem;
    font-size: 1rem;
    line-height: 1.8;
    color: #64748b;
}

.stat-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.875rem;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: #f8fafc;
    padding: 1rem;
    transition:
        transform 220ms ease,
        border-color 220ms ease,
        box-shadow 220ms ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    border-color: #b8d8d1;
    box-shadow: 0 18px 45px -30px rgba(15, 45, 46, 0.4);
}

.reveal-enabled [data-reveal] {
    opacity: 0;
    transform: translateY(30px);
    transition:
        opacity 700ms cubic-bezier(0.22, 1, 0.36, 1),
        transform 700ms cubic-bezier(0.22, 1, 0.36, 1);
    transition-delay: var(--reveal-delay, 0ms);
    will-change: opacity, transform;
}

.reveal-enabled [data-reveal="left"] {
    transform: translateX(-34px);
}

.reveal-enabled [data-reveal="right"] {
    transform: translateX(34px);
}

.reveal-enabled [data-reveal="scale"] {
    transform: translateY(20px) scale(0.97);
}

.reveal-enabled [data-reveal].is-revealed {
    opacity: 1;
    transform: none;
}

.reveal-enabled .stat-card.is-revealed:hover,
.reveal-enabled .step-card.is-revealed:hover,
.reveal-enabled .review-card.is-revealed:hover {
    transform: translateY(-4px);
}

.step-card {
    position: relative;
    border: 1px solid #e2e8f0;
    border-radius: 1.5rem;
    background: white;
    padding: 1.5rem;
    transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
}

.step-card:hover {
    transform: translateY(-5px);
    border-color: #99d7c8;
    box-shadow: 0 20px 50px -30px rgba(15, 45, 46, 0.45);
}

.step-icon {
    position: relative;
    z-index: 1;
    display: flex;
    width: 4.5rem;
    height: 4.5rem;
    align-items: center;
    justify-content: center;
    border-radius: 1.25rem;
    background: #e4f4ef;
    color: #0f766e;
}

.step-number {
    position: absolute;
    top: -0.5rem;
    right: -0.5rem;
    display: flex;
    width: 1.75rem;
    height: 1.75rem;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: #0c292b;
    color: white;
    font-size: 0.625rem;
    font-weight: 800;
}

.feature-line {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
}

.feature-line > svg {
    margin-top: 0.125rem;
    flex: none;
}

.notification-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.05);
    padding: 0.625rem 0.875rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #cbd5e1;
}

.owner-card {
    min-height: 13.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 1.5rem;
    background-color: white;
    padding: 1.5rem;
}

.owner-card h3 {
    margin-top: 2rem;
    font-size: 1rem;
    font-weight: 750;
    color: #0f172a;
}

.owner-card p {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.65;
    color: #64748b;
}

.owner-card--wallet {
    background-color: #f0d3ad;
    border-color: #eac493;
}

.owner-card--wallet h3,
.owner-card--wallet p {
    color: #56361f;
}

.review-card {
    display: flex;
    min-height: 19rem;
    flex-direction: column;
    border: 1px solid #e2e8f0;
    border-radius: 1.5rem;
    background: white;
    padding: 1.5rem;
    transition: transform 220ms ease, box-shadow 220ms ease;
}

.review-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 22px 55px -35px rgba(15, 45, 46, 0.45);
}

.review-card > div:last-child {
    margin-top: auto;
}

.faq-item summary::-webkit-details-marker {
    display: none;
}

@media (prefers-reduced-motion: reduce) {
    .reveal-enabled [data-reveal] {
        opacity: 1;
        transform: none;
        transition: none;
    }

    .stat-card,
    .step-card,
    .review-card {
        transition: none;
    }
}
</style>
