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
        <!-- 1. Modern Hero with Glassmorphism -->
        <section class="relative isolate overflow-hidden bg-[#02080a] text-white pb-32 pt-20">
            <div class="absolute inset-0 -z-10">
                <div class="absolute -left-20 top-0 h-[600px] w-[600px] rounded-full bg-gradient-to-tr from-teal-500/20 to-emerald-400/20 blur-[120px]" />
                <div class="absolute -right-20 bottom-0 h-[600px] w-[600px] rounded-full bg-gradient-to-bl from-orange-400/20 to-rose-400/20 blur-[130px]" />
                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 h-[800px] w-[1200px] rounded-full bg-white/[0.02] blur-[100px]" />
                <div class="hero-grid absolute inset-0 opacity-40 mix-blend-overlay" />
            </div>

            <div class="mx-auto grid min-h-[calc(100vh-6rem)] max-w-7xl grid-cols-1 items-center gap-12 px-4 pb-24 pt-14 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:gap-8 lg:px-8 lg:pb-32 lg:pt-20">
                <div class="relative z-10 max-w-2xl">
                    <div class="group mb-8 inline-flex items-center gap-3 rounded-full border border-white/10 bg-white/5 p-1 pr-4 text-xs font-semibold tracking-wide text-teal-100 shadow-2xl backdrop-blur-xl transition-all hover:bg-white/10">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-teal-400/20 text-teal-300">
                            <Star class="h-3.5 w-3.5" />
                        </span>
                        Ekosistem kos modern terintegrasi
                    </div>

                    <h1 class="max-w-2xl text-5xl font-extrabold leading-[1.1] tracking-tight text-white sm:text-6xl lg:text-[4.75rem]">
                        Temukan ruang untuk
                        <span class="inline-block bg-gradient-to-r from-teal-300 via-emerald-300 to-orange-300 bg-clip-text text-transparent pb-2">hidup lebih dekat.</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-300 sm:text-xl font-medium">
                        Jelajahi kos premium melalui peta interaktif, periksa kamar dan ulasan terverifikasi, lalu kelola sewa serta pembayaran dari satu tempat yang transparan.
                    </p>

                    <div class="mt-10 flex flex-col gap-4 sm:flex-row">
                        <Link :href="route('public.kos.index')">
                            <Button class="group relative h-14 w-full overflow-hidden rounded-full bg-gradient-to-r from-[#f7924a] to-[#ffaa66] px-8 text-base font-bold text-[#172225] shadow-[0_0_40px_-10px_rgba(247,146,74,0.8)] transition-all hover:scale-105 hover:shadow-[0_0_60px_-15px_rgba(247,146,74,1)] sm:w-auto">
                                <span class="relative z-10 flex items-center gap-2">
                                    Jelajahi Kos Sekarang
                                    <ArrowRight class="h-5 w-5 transition-transform group-hover:translate-x-1" />
                                </span>
                                <div class="absolute inset-0 z-0 bg-white/20 opacity-0 transition-opacity group-hover:opacity-100" />
                            </Button>
                        </Link>
                        <a href="#cara-kerja">
                            <Button variant="outline" class="h-14 w-full rounded-full border-white/10 bg-white/5 px-8 text-base font-bold text-white backdrop-blur-md transition-all hover:bg-white/10 hover:text-white sm:w-auto">
                                Lihat Cara Kerja
                            </Button>
                        </a>
                    </div>

                    <div class="mt-12 flex flex-wrap items-center gap-x-8 gap-y-4 text-sm font-medium text-slate-300">
                        <span class="flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-500/20">
                                <Check class="h-3.5 w-3.5 text-teal-300" />
                            </div>
                            Pencarian berbasis peta
                        </span>
                        <span class="flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-500/20">
                                <Check class="h-3.5 w-3.5 text-teal-300" />
                            </div>
                            Data ditinjau admin
                        </span>
                        <span class="flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-500/20">
                                <Check class="h-3.5 w-3.5 text-teal-300" />
                            </div>
                            Pembayaran aman
                        </span>
                    </div>
                </div>

                <div class="relative min-h-[460px] lg:min-h-[650px] w-full">
                    <div class="absolute inset-0 bg-gradient-to-tr from-teal-500/10 to-transparent rounded-[3rem] blur-2xl transform rotate-3 scale-105" />
                    <LandingHeroScene class="relative z-10" />

                    <!-- Floating glass card 1 -->
                    <div class="absolute -bottom-6 left-4 z-20 animate-float rounded-2xl border border-white/20 bg-white/10 p-5 shadow-[0_8px_32px_0_rgba(0,0,0,0.3)] backdrop-blur-xl sm:left-10 lg:bottom-10">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-teal-400 to-emerald-500 shadow-inner">
                                <MapPin class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-teal-100 uppercase tracking-wider">Siap dijelajahi</p>
                                <p class="text-lg font-bold text-white">{{ formatNumber(stats.publishedKos) }} kos premium</p>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

            <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-white via-white/80 to-transparent" />
        </section>



        <!-- 4. Animated map discovery -->
        <section id="peta-kos" class="scroll-mt-20 relative overflow-hidden bg-gradient-to-b from-slate-50 to-[#f3f7f5] py-20 sm:py-32">
            <div class="absolute -right-40 top-0 h-[500px] w-[500px] rounded-full bg-teal-400/10 blur-[100px]" />
            <div class="absolute -left-40 bottom-0 h-[400px] w-[400px] rounded-full bg-emerald-400/10 blur-[100px]" />
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8 relative z-10">
                <div data-reveal="left" class="max-w-xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-teal-700 border border-teal-100 mb-6">
                        <Map class="h-3.5 w-3.5" /> Eksplorasi Visual
                    </div>
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">Pencarian cerdas<br />di area tujuan.</h2>
                    <p class="mt-6 text-base leading-relaxed text-slate-600 sm:text-lg">
                        Gunakan peta interaktif untuk melihat persebaran kos secara visual. Tentukan lokasi pusat, sesuaikan radius, dan temukan properti yang tepat dalam jangkauan Anda.
                    </p>

                    <div class="mt-10 space-y-6">
                        <div class="group flex items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-teal-600 shadow-sm transition-all group-hover:scale-110 group-hover:bg-teal-600 group-hover:text-white group-hover:shadow-teal-600/30">
                                <LocateFixed class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Lokasi sebagai titik pusat</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500">Gunakan posisi Anda saat ini atau cari area spesifik untuk memulai penjelajahan.</p>
                            </div>
                        </div>
                        <div class="group flex items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-teal-600 shadow-sm transition-all group-hover:scale-110 group-hover:bg-teal-600 group-hover:text-white group-hover:shadow-teal-600/30">
                                <SlidersHorizontal class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Radius yang fleksibel</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500">Persempit atau perluas jangkauan pencarian kos sesuai kebutuhan jarak Anda.</p>
                            </div>
                        </div>
                        <div class="group flex items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-teal-600 shadow-sm transition-all group-hover:scale-110 group-hover:bg-teal-600 group-hover:text-white group-hover:shadow-teal-600/30">
                                <MapPin class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Detail instan dari marker</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500">Pilih titik untuk mengintip harga dan rating sebelum membuka halaman detail lengkap.</p>
                            </div>
                        </div>
                    </div>

                    <Link :href="route('public.kos.index')" class="mt-10 inline-flex">
                        <Button class="group h-12 rounded-full bg-[#0c292b] px-6 text-sm font-bold text-white shadow-lg hover:bg-[#143b3e] transition-all hover:shadow-xl hover:-translate-y-1">
                            Buka Peta Interaktif
                            <ArrowRight class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </Button>
                    </Link>
                </div>

                <div data-reveal="right" class="relative">
                    <div class="absolute -inset-1 rounded-[2.5rem] bg-gradient-to-tr from-teal-200 to-[#f7924a]/30 opacity-60 blur-xl" />
                    <div class="relative rounded-[2rem] border border-white bg-white/50 p-2 shadow-2xl backdrop-blur-sm">
                        <LandingMapDiscovery :items="mapKos" class="overflow-hidden rounded-3xl" />
                    </div>
                    <p class="mt-4 text-center text-xs font-medium text-slate-400">
                        Pratinjau interaktif dari properti berkoordinat
                    </p>
                </div>
            </div>
        </section>

        <!-- 6. How it works -->
        <section id="cara-kerja" class="scroll-mt-20 bg-white py-24 sm:py-32 relative overflow-hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600 bg-teal-50 px-4 py-1.5 rounded-full inline-block mb-4">Dari pencarian sampai pindah</p>
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">Perjalanan yang sangat mulus.</h2>
                    <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-slate-600">Alur terintegrasi kami dirancang untuk menghilangkan friksi. Temukan, pesan, dan bayar tanpa harus meninggalkan platform.</p>
                </div>

                <div class="relative mt-20 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="absolute left-[12.5%] right-[12.5%] top-10 hidden border-t-2 border-dashed border-slate-200 lg:block" />

                    <article data-reveal="scale" class="step-card group relative bg-white border border-slate-100 p-8 rounded-[2rem] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-all hover:shadow-[0_20px_40px_-15px_rgba(15,45,46,0.15)] hover:border-teal-200 hover:-translate-y-2" style="--reveal-delay: 0ms">
                        <div class="absolute -right-4 -top-4 text-8xl font-black text-slate-50 opacity-50 transition-opacity group-hover:opacity-100 group-hover:text-teal-50">1</div>
                        <div class="relative z-10 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-50 to-emerald-100 text-teal-700 shadow-inner group-hover:from-teal-500 group-hover:to-emerald-600 group-hover:text-white transition-colors">
                            <Search class="h-7 w-7" />
                        </div>
                        <h3 class="relative z-10 mt-8 text-xl font-bold text-slate-900">Cari dari peta</h3>
                        <p class="relative z-10 mt-3 text-sm leading-relaxed text-slate-500">Eksplorasi visual pada area pilihan dengan filter pencarian cerdas.</p>
                    </article>

                    <article data-reveal="scale" class="step-card group relative bg-white border border-slate-100 p-8 rounded-[2rem] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-all hover:shadow-[0_20px_40px_-15px_rgba(15,45,46,0.15)] hover:border-teal-200 hover:-translate-y-2" style="--reveal-delay: 90ms">
                        <div class="absolute -right-4 -top-4 text-8xl font-black text-slate-50 opacity-50 transition-opacity group-hover:opacity-100 group-hover:text-teal-50">2</div>
                        <div class="relative z-10 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-50 to-emerald-100 text-teal-700 shadow-inner group-hover:from-teal-500 group-hover:to-emerald-600 group-hover:text-white transition-colors">
                            <MapPin class="h-7 w-7" />
                        </div>
                        <h3 class="relative z-10 mt-8 text-xl font-bold text-slate-900">Periksa detail</h3>
                        <p class="relative z-10 mt-3 text-sm leading-relaxed text-slate-500">Tinjau foto lengkap, fasilitas, kebijakan, dan ulasan terverifikasi.</p>
                    </article>

                    <article data-reveal="scale" class="step-card group relative bg-white border border-slate-100 p-8 rounded-[2rem] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-all hover:shadow-[0_20px_40px_-15px_rgba(15,45,46,0.15)] hover:border-teal-200 hover:-translate-y-2" style="--reveal-delay: 180ms">
                        <div class="absolute -right-4 -top-4 text-8xl font-black text-slate-50 opacity-50 transition-opacity group-hover:opacity-100 group-hover:text-teal-50">3</div>
                        <div class="relative z-10 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-50 to-emerald-100 text-teal-700 shadow-inner group-hover:from-teal-500 group-hover:to-emerald-600 group-hover:text-white transition-colors">
                            <ClipboardCheck class="h-7 w-7" />
                        </div>
                        <h3 class="relative z-10 mt-8 text-xl font-bold text-slate-900">Ajukan sewa</h3>
                        <p class="relative z-10 mt-3 text-sm leading-relaxed text-slate-500">Pilih kamar dan mulai pengajuan sewa hanya dengan beberapa klik.</p>
                    </article>

                    <article data-reveal="scale" class="step-card group relative bg-white border border-slate-100 p-8 rounded-[2rem] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)] transition-all hover:shadow-[0_20px_40px_-15px_rgba(15,45,46,0.15)] hover:border-teal-200 hover:-translate-y-2" style="--reveal-delay: 270ms">
                        <div class="absolute -right-4 -top-4 text-8xl font-black text-slate-50 opacity-50 transition-opacity group-hover:opacity-100 group-hover:text-teal-50">4</div>
                        <div class="relative z-10 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-50 to-emerald-100 text-teal-700 shadow-inner group-hover:from-teal-500 group-hover:to-emerald-600 group-hover:text-white transition-colors">
                            <KeyRound class="h-7 w-7" />
                        </div>
                        <h3 class="relative z-10 mt-8 text-xl font-bold text-slate-900">Kelola mudah</h3>
                        <p class="relative z-10 mt-3 text-sm leading-relaxed text-slate-500">Pantau siklus tagihan dan pembayaran pada satu dashboard modern.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- 7. Security & transparency -->
        <section id="keamanan" class="scroll-mt-20 bg-slate-50 py-24 sm:py-32 relative overflow-hidden">
            <div class="absolute inset-0 opacity-30 bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:16px_16px]" />
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid items-center gap-14 lg:grid-cols-[0.8fr_1.2fr]">
                    <div data-reveal="left">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-bold uppercase tracking-wider text-teal-700 shadow-sm border border-slate-100 mb-6">
                            <ShieldCheck class="h-3.5 w-3.5" /> Rasa Aman
                        </div>
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">Transparansi yang bisa Anda percayai.</h2>
                        <p class="mt-6 text-base leading-relaxed text-slate-600 sm:text-lg">
                            Platform kami memiliki lapisan verifikasi dan sistem umpan balik dari komunitas untuk memastikan kualitas kos tetap terjaga.
                        </p>
                        <Link :href="route('public.kos.index')" class="group mt-10 inline-flex items-center gap-2 text-sm font-bold text-teal-700 hover:text-teal-900 bg-white px-5 py-3 rounded-full shadow-sm border border-slate-200 transition-all hover:shadow-md">
                            Periksa properti terverifikasi
                            <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </div>

                    <div data-reveal="right" class="grid gap-4 sm:grid-cols-2">
                        <article class="relative overflow-hidden rounded-[2.5rem] bg-gradient-to-br from-[#0c292b] to-[#123e42] p-8 text-white sm:row-span-2 shadow-2xl">
                            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-teal-400/20 blur-2xl" />
                            <div class="relative z-10 flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-400/20 text-teal-300 backdrop-blur-md border border-white/10">
                                <ShieldCheck class="h-7 w-7" />
                            </div>
                            <h3 class="relative z-10 mt-8 text-2xl font-bold">Verifikasi Ketat</h3>
                            <p class="relative z-10 mt-3 text-sm leading-relaxed text-teal-50">Data properti dan dokumen legal ditinjau secara manual oleh tim kami sebelum kos dipublikasikan ke publik.</p>

                            <div class="relative z-10 mt-8 space-y-4 border-t border-white/10 pt-8">
                                <div class="flex items-center gap-4 text-sm font-medium">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 border border-white/5"><Check class="h-4 w-4 text-teal-300" /></span>
                                    Validasi lokasi akurat
                                </div>
                                <div class="flex items-center gap-4 text-sm font-medium">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 border border-white/5"><Check class="h-4 w-4 text-teal-300" /></span>
                                    Kesesuaian foto
                                </div>
                                <div class="flex items-center gap-4 text-sm font-medium">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 border border-white/5"><Check class="h-4 w-4 text-teal-300" /></span>
                                    Pengecekan dokumen legal
                                </div>
                            </div>
                        </article>

                        <article class="rounded-[2.5rem] border border-slate-100 bg-white p-7 shadow-xl shadow-slate-200/40 transition-transform hover:-translate-y-1">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-500 mb-5">
                                <Star class="h-6 w-6 fill-amber-500" />
                            </div>
                            <h3 class="font-bold text-slate-900 text-lg">Ulasan Asli</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-500">Baca opini nyata dari sesama penyewa yang pernah menetap.</p>
                        </article>

                        <article class="rounded-[2.5rem] border border-slate-100 bg-white p-7 shadow-xl shadow-slate-200/40 transition-transform hover:-translate-y-1">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-500 mb-5">
                                <Flag class="h-6 w-6" />
                            </div>
                            <h3 class="font-bold text-slate-900 text-lg">Laporan Cepat</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-500">Sistem pelaporan efisien untuk menindaklanjuti properti bermasalah.</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Tenancy and payment -->
        <section class="overflow-hidden bg-white py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-14 lg:grid-cols-2">
                    <div data-reveal="left" class="order-2 lg:order-1 relative">
                        <div class="absolute -left-10 top-1/2 -translate-y-1/2 h-80 w-80 rounded-full bg-teal-100/50 blur-[80px] -z-10" />
                        <div class="relative rounded-[2.5rem] bg-gradient-to-br from-teal-50 to-emerald-50 p-4 sm:p-8 shadow-inner border border-teal-100/50">
                            <div class="rounded-3xl border border-white/50 bg-white/80 p-6 shadow-2xl backdrop-blur-xl sm:p-8">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-6">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Ringkasan Sewa</p>
                                        <p class="mt-2 text-xl font-bold text-slate-900">Invoice Bulanan</p>
                                    </div>
                                    <span class="rounded-full bg-amber-100 px-4 py-1.5 text-xs font-bold text-amber-700 border border-amber-200 animate-pulse">Menunggu Pembayaran</span>
                                </div>

                                <div class="mt-6 grid grid-cols-2 gap-4">
                                    <div class="rounded-2xl bg-white border border-slate-100 p-5 shadow-sm">
                                        <ReceiptText class="h-6 w-6 text-teal-600 mb-4" />
                                        <p class="text-xs font-medium text-slate-400">Periode Sewa</p>
                                        <p class="mt-1 text-base font-bold text-slate-800">Bulanan</p>
                                    </div>
                                    <div class="rounded-2xl bg-white border border-slate-100 p-5 shadow-sm">
                                        <History class="h-6 w-6 text-blue-500 mb-4" />
                                        <p class="text-xs font-medium text-slate-400">Status Sewa</p>
                                        <p class="mt-1 text-base font-bold text-slate-800">Aktif</p>
                                    </div>
                                </div>

                                <div class="mt-5 flex items-center justify-between rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 p-5 text-white shadow-lg transition-transform hover:scale-[1.02]">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md">
                                            <CreditCard class="h-5 w-5 text-teal-300" />
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-300">Metode Pembayaran</p>
                                            <p class="text-sm font-bold">Duitku / Transfer Bank</p>
                                        </div>
                                    </div>
                                    <ArrowRight class="h-5 w-5 text-teal-300" />
                                </div>
                            </div>

                            <div class="absolute -bottom-8 -right-6 hidden rounded-2xl border border-white/40 bg-white/70 p-5 shadow-2xl backdrop-blur-lg sm:block animate-float">
                                <div class="flex items-center gap-4">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                                        <CheckCheck class="h-6 w-6" />
                                    </span>
                                    <div>
                                        <p class="text-xs font-medium text-slate-500">Notifikasi Real-time</p>
                                        <p class="text-sm font-bold text-slate-900">Pembayaran Terkonfirmasi</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div data-reveal="right" class="order-1 lg:order-2">
                        <div class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-teal-700 border border-teal-100 mb-6">
                            <ReceiptText class="h-3.5 w-3.5" /> Administrasi Pintar
                        </div>
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">Administrasi sewa tanpa rasa pusing.</h2>
                        <p class="mt-6 text-base leading-relaxed text-slate-600 sm:text-lg">
                            Sinkronisasi data otomatis antara penyewa dan pemilik. Invoice tercatat rapi, bukti tersimpan aman, dan status sewa termonitor dengan akurat.
                        </p>

                        <div class="mt-10 grid gap-6 sm:grid-cols-2">
                            <div class="group flex gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 transition-colors group-hover:bg-teal-600 group-hover:text-white">
                                    <ReceiptText class="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900">Invoice Terstruktur</h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">Detail periode, jatuh tempo, dan nominal tersaji jelas.</p>
                                </div>
                            </div>
                            <div class="group flex gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 transition-colors group-hover:bg-teal-600 group-hover:text-white">
                                    <UploadCloud class="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900">Unggah Praktis</h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">Sistem unggah bukti bayar yang ringan dan mudah digunakan.</p>
                                </div>
                            </div>
                            <div class="group flex gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 transition-colors group-hover:bg-teal-600 group-hover:text-white">
                                    <CreditCard class="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900">Gateway Otomatis</h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">Konfirmasi instan dengan integrasi gateway Duitku.</p>
                                </div>
                            </div>
                            <div class="group flex gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 transition-colors group-hover:bg-teal-600 group-hover:text-white">
                                    <History class="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900">Riwayat Terarsip</h3>
                                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">Akses histori pembayaran kapan saja Anda butuhkan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. WhatsApp notifications -->
        <section class="relative bg-gradient-to-br from-[#051114] to-[#0a2327] py-24 text-white sm:py-32 overflow-hidden">
            <div class="absolute right-0 top-0 h-[600px] w-[600px] rounded-full bg-teal-600/10 blur-[120px] mix-blend-screen" />
            <div class="absolute left-0 bottom-0 h-[600px] w-[600px] rounded-full bg-[#f7924a]/10 blur-[120px] mix-blend-screen" />
            
            <div class="mx-auto grid max-w-7xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-2 lg:px-8 relative z-10">
                <div data-reveal="left">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-teal-300 border border-white/10 mb-6 backdrop-blur-md">
                        <MessageCircle class="h-3.5 w-3.5" /> Notifikasi WhatsApp
                    </div>
                    <h2 class="text-3xl font-extrabold tracking-tight sm:text-5xl lg:text-5xl text-white">Informasi penting hadir di kanal yang familiar.</h2>
                    <p class="mt-6 max-w-xl text-base leading-relaxed text-slate-300 sm:text-lg">
                        Sistem kami bekerja di balik layar, secara otomatis mengirimkan pemberitahuan instan tentang status pemesanan, konfirmasi pembayaran, dan pengingat invoice langsung ke WhatsApp Anda.
                    </p>

                    <div class="mt-10 flex flex-wrap gap-4">
                        <div class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-5 py-2.5 backdrop-blur-sm transition-colors hover:bg-white/10">
                            <BellRing class="h-5 w-5 text-teal-400" />
                            <span class="text-sm font-semibold">Pengajuan Sewa</span>
                        </div>
                        <div class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-5 py-2.5 backdrop-blur-sm transition-colors hover:bg-white/10">
                            <ReceiptText class="h-5 w-5 text-teal-400" />
                            <span class="text-sm font-semibold">Pengingat Invoice</span>
                        </div>
                        <div class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-5 py-2.5 backdrop-blur-sm transition-colors hover:bg-white/10">
                            <CheckCheck class="h-5 w-5 text-teal-400" />
                            <span class="text-sm font-semibold">Status Pembayaran</span>
                        </div>
                    </div>
                </div>

                <div data-reveal="right" class="relative mx-auto w-full max-w-lg">
                    <div class="relative rounded-[2.5rem] border border-white/10 bg-gradient-to-b from-white/10 to-white/5 p-6 backdrop-blur-2xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.5)]">
                        <div class="mb-6 flex items-center gap-4 border-b border-white/10 pb-5">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#25D366] to-[#128C7E] shadow-lg">
                                <MessageCircle class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-base font-bold text-white">Kos Online Bot</p>
                                <p class="flex items-center gap-1 text-xs text-teal-200"><span class="h-1.5 w-1.5 rounded-full bg-[#25D366] animate-pulse"></span> Sedang aktif</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="max-w-[85%] rounded-[1.25rem] rounded-tl-sm bg-white p-5 text-[#183334] shadow-md transform transition-transform hover:scale-[1.02]">
                                <div class="flex items-center gap-2 mb-2">
                                    <ReceiptText class="h-4 w-4 text-amber-500" />
                                    <p class="text-sm font-bold">Pengingat Pembayaran</p>
                                </div>
                                <p class="text-sm leading-relaxed text-slate-600">Invoice masa sewa Anda untuk kamar <strong>A-01</strong> akan jatuh tempo besok. Silakan periksa detailnya.</p>
                                <p class="mt-2 text-right text-[10px] font-bold text-slate-400">09.15</p>
                            </div>
                            <div class="ml-auto max-w-[85%] rounded-[1.25rem] rounded-tr-sm bg-gradient-to-br from-[#dcf8c6] to-[#d8f7e9] p-5 text-[#183334] shadow-md transform transition-transform hover:scale-[1.02]">
                                <p class="text-sm leading-relaxed font-medium">Pembayaran sudah saya selesaikan via transfer bank! Terima kasih.</p>
                                <p class="mt-2 flex items-center justify-end gap-1 text-[10px] font-bold text-teal-800">09.18 <CheckCheck class="h-3.5 w-3.5 text-blue-500" /></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 10. Owner solution -->
        <section id="pemilik" class="scroll-mt-20 bg-gradient-to-b from-white to-slate-50 py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-14 lg:grid-cols-[0.8fr_1.2fr] items-center">
                    <div data-reveal="left">
                        <div class="inline-flex items-center gap-2 rounded-full bg-orange-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-orange-700 border border-orange-100 mb-6">
                            <Building2 class="h-3.5 w-3.5" /> Solusi Pemilik
                        </div>
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">Kendalikan properti Anda dari satu dashboard.</h2>
                        <p class="mt-6 text-base leading-relaxed text-slate-600 sm:text-lg">
                            Otomatiskan operasional harian kos Anda. Mulai dari publikasi listing, manajemen penyewa, hingga pemantauan pendapatan. Semuanya tersentralisasi.
                        </p>

                        <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')" class="mt-10 inline-block">
                            <Button class="group h-14 rounded-full bg-gradient-to-r from-slate-900 to-slate-800 px-8 text-sm font-bold text-white shadow-xl hover:shadow-2xl transition-all hover:-translate-y-1">
                                Mulai Jadi Mitra
                                <ArrowRight class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </Button>
                        </Link>
                        <Link v-else-if="$page.props.auth.user?.role === 'pemilik_kos'" :href="route('owner.dashboard')" class="mt-10 inline-block">
                            <Button class="group h-14 rounded-full bg-gradient-to-r from-slate-900 to-slate-800 px-8 text-sm font-bold text-white shadow-xl hover:shadow-2xl transition-all hover:-translate-y-1">
                                Buka Dashboard Pemilik
                                <ArrowRight class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </Button>
                        </Link>
                    </div>

                    <div data-reveal="right" class="grid gap-6 sm:grid-cols-2">
                        <article class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all hover:shadow-[0_20px_40px_rgb(0,0,0,0.08)] hover:-translate-y-2">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-teal-600 mb-6">
                                <Building2 class="h-7 w-7" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">Manajemen Properti</h3>
                            <p class="mt-3 text-sm leading-relaxed text-slate-500">Atur profil, foto, fasilitas, tipe kamar, dan harga sewa dengan mudah.</p>
                        </article>
                        
                        <article class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all hover:shadow-[0_20px_40px_rgb(0,0,0,0.08)] hover:-translate-y-2">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 mb-6">
                                <FileCheck2 class="h-7 w-7" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">Validasi Legalitas</h3>
                            <p class="mt-3 text-sm leading-relaxed text-slate-500">Unggah dokumen resmi untuk mendapatkan badge terverifikasi.</p>
                        </article>

                        <article class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all hover:shadow-[0_20px_40px_rgb(0,0,0,0.08)] hover:-translate-y-2">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 mb-6">
                                <BarChart3 class="h-7 w-7" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">Pantau Pembayaran</h3>
                            <p class="mt-3 text-sm leading-relaxed text-slate-500">Lacak penyewa aktif, konfirmasi invoice otomatis, dan hunian Anda.</p>
                        </article>

                        <article class="relative overflow-hidden rounded-[2rem] border border-orange-100 bg-gradient-to-br from-orange-50 to-amber-50 p-8 shadow-[0_8px_30px_rgb(247,146,74,0.15)] transition-all hover:shadow-[0_20px_40px_rgb(247,146,74,0.25)] hover:-translate-y-2">
                            <div class="absolute -right-4 -bottom-4 h-32 w-32 rounded-full bg-orange-400/10 blur-xl" />
                            <div class="relative z-10 flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-orange-600 mb-6 shadow-sm">
                                <WalletCards class="h-7 w-7" />
                            </div>
                            <h3 class="relative z-10 text-xl font-bold text-slate-900">Dompet Digital</h3>
                            <p class="relative z-10 mt-3 text-sm leading-relaxed text-slate-600">Terima pembayaran dan cairkan saldo langsung ke rekening Anda.</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- 11. Tenant reviews -->
        <section id="ulasan" class="scroll-mt-20 bg-white py-24 sm:py-32 relative overflow-hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div data-reveal class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between mb-16">
                    <div class="max-w-2xl">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600 bg-teal-50 px-4 py-1.5 rounded-full inline-block mb-4">Suara Penyewa</p>
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">Cerita nyata dari mereka yang sudah mencoba.</h2>
                    </div>
                    <Link :href="route('public.kos.index')" class="group inline-flex shrink-0 items-center gap-2 text-sm font-bold text-teal-700 hover:text-teal-900 bg-slate-50 px-5 py-3 rounded-full border border-slate-200 transition-all hover:bg-slate-100">
                        Jelajahi semua kos
                        <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </Link>
                </div>

                <div v-if="featuredReviews.length" class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="(review, index) in featuredReviews"
                        :key="review.id"
                        data-reveal="scale"
                        class="group relative rounded-[2rem] border border-slate-100 bg-white p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all hover:shadow-[0_20px_40px_rgb(0,0,0,0.08)] hover:-translate-y-2"
                        :style="{ '--reveal-delay': `${(index % 3) * 90}ms` }"
                    >
                        <div class="absolute right-8 top-8 opacity-10 transition-opacity group-hover:opacity-20 text-teal-600">
                            <Quote class="h-12 w-12" />
                        </div>
                        <div class="flex gap-1" :aria-label="`${review.rating} dari 5 bintang`">
                            <Star
                                v-for="score in 5"
                                :key="score"
                                class="h-5 w-5"
                                :class="score <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-200'"
                            />
                        </div>

                        <p class="mt-6 line-clamp-4 text-base leading-relaxed text-slate-600 italic">“{{ review.comment }}”</p>

                        <div class="mt-8 flex items-center gap-4 border-t border-slate-100 pt-6">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-teal-100 to-emerald-100 text-sm font-bold text-teal-800 shadow-inner">
                                {{ initials(review.user?.name) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900">{{ review.user?.name }}</p>
                                <p class="truncate text-xs font-medium text-slate-500">{{ review.boarding_house?.name }}</p>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="mt-14 rounded-[2.5rem] border border-dashed border-slate-200 bg-slate-50/50 px-6 py-20 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white shadow-sm mb-5">
                        <MessageCircle class="h-8 w-8 text-slate-300" />
                    </div>
                    <h3 class="font-bold text-xl text-slate-800">Belum ada ulasan yang dipublikasikan</h3>
                    <p class="mt-3 text-sm text-slate-500 max-w-md mx-auto">Ulasan penyewa akan muncul di sini setelah mereka membagikan pengalaman pada detail kos.</p>
                </div>
            </div>
        </section>

        <!-- 12. FAQ -->
        <section class="bg-gradient-to-b from-slate-50 to-[#eef4f1] py-24 sm:py-32 relative">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
                <div data-reveal="left" class="lg:sticky lg:top-32 lg:h-fit">
                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-600 text-white shadow-lg shadow-teal-600/30 mb-6">
                        <HelpCircle class="h-7 w-7" />
                    </div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-600 mb-3">Pertanyaan Umum</p>
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Hal penting yang sering ditanyakan.</h2>
                    <p class="mt-6 text-base leading-relaxed text-slate-600">Punya pertanyaan? Temukan jawaban singkat mengenai pencarian, penyewaan, pembayaran, laporan, dan pengelolaan kos di bawah ini.</p>
                </div>

                <div data-reveal="right" class="space-y-4">
                    <details v-for="faq in faqs" :key="faq.question" class="group rounded-[1.5rem] border border-slate-200/60 bg-white p-6 shadow-sm transition-all open:border-teal-200 open:shadow-md hover:border-teal-100">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-5 font-bold text-slate-900 outline-none">
                            <span class="text-lg">{{ faq.question }}</span>
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-500 transition-all group-open:rotate-180 group-open:bg-teal-50 group-open:text-teal-600 group-hover:bg-teal-50 group-hover:text-teal-600">
                                <ChevronDown class="h-5 w-5" />
                            </span>
                        </summary>
                        <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-600 pr-12 border-t border-slate-100 pt-4">{{ faq.answer }}</p>
                    </details>
                </div>
            </div>
        </section>

        <!-- 13. Final CTA -->
        <section class="bg-white px-4 py-20 sm:px-6 sm:py-32 lg:px-8">
            <div data-reveal="scale" class="relative mx-auto max-w-7xl overflow-hidden rounded-[2.5rem] bg-[#02080a] px-6 py-16 text-center text-white sm:px-12 sm:py-24 shadow-2xl">
                <div class="absolute inset-0 bg-gradient-to-br from-teal-900/40 via-transparent to-orange-900/30 mix-blend-overlay" />
                <div class="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-teal-500/20 blur-[80px]" />
                <div class="absolute -bottom-32 -right-20 h-96 w-96 rounded-full bg-[#f7924a]/20 blur-[100px]" />
                <div class="hero-grid absolute inset-0 opacity-20 mix-blend-overlay" />

                <div class="relative mx-auto max-w-3xl z-10">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/5 border border-white/10 px-4 py-1.5 backdrop-blur-md mb-6">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal-400 opacity-75" />
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-teal-400" />
                        </span>
                        <p class="text-xs font-bold uppercase tracking-[0.15em] text-teal-200">Mulai Perjalanan Anda</p>
                    </div>
                    <h2 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl text-white">
                        Wujudkan <span class="text-transparent bg-clip-text bg-gradient-to-r from-teal-300 to-emerald-300">kenyamanan</span> hari ini.
                    </h2>
                    <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-slate-300 sm:text-lg font-medium">Pilih jalur Anda dan gunakan fitur eksklusif yang dirancang khusus untuk memenuhi kebutuhan gaya hidup penyewa maupun efisiensi pemilik kos.</p>

                    <div class="mt-10 flex flex-col justify-center gap-4 sm:flex-row">
                        <Link :href="route('public.kos.index')">
                            <Button class="group h-14 w-full rounded-full bg-gradient-to-r from-[#f7924a] to-[#ffaa66] px-8 font-bold text-[#172225] shadow-[0_0_30px_-5px_rgba(247,146,74,0.6)] transition-all hover:scale-105 hover:shadow-[0_0_40px_-5px_rgba(247,146,74,0.8)] sm:w-auto">
                                Mulai Cari Kos
                                <Search class="h-4 w-4 ml-2 transition-transform group-hover:rotate-12" />
                            </Button>
                        </Link>
                        <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')">
                            <Button variant="outline" class="group h-14 w-full rounded-full border-white/20 bg-white/5 px-8 font-bold text-white backdrop-blur-md transition-all hover:bg-white/15 hover:border-white/30 sm:w-auto">
                                Daftarkan Kos Anda
                                <Building2 class="h-4 w-4 ml-2 transition-transform group-hover:-translate-y-1" />
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
@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-15px); }
    100% { transform: translateY(0px); }
}
@keyframes float-delayed {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}
.animate-float {
    animation: float 6s ease-in-out infinite;
}
.animate-float-delayed {
    animation: float-delayed 7s ease-in-out infinite 2s;
}

.hero-grid {
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
    background-size: 64px 64px;
    mask-image: radial-gradient(circle at center, black 40%, transparent 80%);
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
    flex-direction: column;
    align-items: flex-start;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 1.5rem;
    background: #ffffff;
    padding: 1.5rem;
    box-shadow: 0 4px 20px -10px rgba(0, 0, 0, 0.05);
    transition:
        transform 300ms cubic-bezier(0.22, 1, 0.36, 1),
        border-color 300ms ease,
        box-shadow 300ms ease;
}

.stat-card:hover {
    transform: translateY(-6px);
    border-color: #99d7c8;
    box-shadow: 0 24px 50px -20px rgba(15, 45, 46, 0.15);
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
