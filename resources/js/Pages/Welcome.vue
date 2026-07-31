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
    Mail,
    Phone,
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
    () => import('@/components/LandingHeroScene.vue'),
);
const LandingMapDiscovery = defineAsyncComponent(
    () => import('@/components/LandingMapDiscovery.vue'),
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
        question: 'Gimana cara cari kosan pakai peta?',
        answer: 'Gampang banget! Buka halaman Cari Kos, izinkan akses lokasi, trus geser-geser petanya. Klik ikon lokasi buat lihat info singkat kosannya. Kalau sreg, klik buat lihat detail kamar, harga, dan baca review jujur.',
    },
    {
        question: 'Harus bikin akun dulu gak sih buat sekadar lihat-lihat?',
        answer: 'Gak perlu! Kamu bebas keliling peta, lihat foto kamar, fasilitas, sampai baca komentar penyewa lain tanpa harus login. Akun baru dibutuhkan kalau kamu udah mantap mau mulai booking kamar.',
    },
    {
        question: 'Proses booking kamarnya ribet gak?',
        answer: 'Sama sekali enggak! Pilih kamar yang masih kosong, tentuin tanggal masuk dan jumlah orang, trus klik ajukan. Nanti semua status pesanan dan tagihannya bisa kamu pantau langsung dari HP.',
    },
    {
        question: 'Bisa bayar pakai metode apa aja?',
        answer: 'Banyak pilihan dan semuanya serba otomatis! Begitu tagihan terbit, kamu tinggal klik link bayar, pilih metode pembayaran online favoritmu, lalu selesaikan. Tagihan otomatis lunas tanpa perlu repot ngirim bukti transfer.',
    },
    {
        question: 'Gimana kalau info atau lokasi kosannya zonk/gak sesuai?',
        answer: 'Langsung aja klik tombol Laporkan Kos di halaman detail. Kasih tahu kami apa masalahnya, biar tim admin kami bisa langsung bertindak dan mastiin platform ini tetep aman buat semua pencari kos.',
    },
    {
        question: 'Kalau daftar jadi pemilik kos, bisa ngatur apa aja?',
        answer: 'Semuanya! Dari mulai update foto kamar, atur harga, kelola anak kos yang aktif, pantau pembayaran, sampai narik saldo pendapatan bulanan. Semua dikerjain sambil ngopi lewat satu dashboard pintar.',
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
        <!-- 1. Modern Hero -->
        <section class="relative isolate overflow-hidden bg-slate-900 text-white pb-32 pt-20 lg:pb-0 lg:min-h-[90vh] flex flex-col justify-center">
            <div class="absolute inset-0 -z-10 bg-gradient-to-b from-slate-900 to-[#071a1d]" />

            <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-8 px-4 pb-16 pt-8 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-0 w-full">
                <div class="relative z-10 max-w-2xl mx-auto flex flex-col items-center text-center">
                    
                    <h1 class="max-w-2xl text-4xl font-extrabold leading-[1.15] tracking-tight text-white sm:text-4xl lg:text-5xl">
                        Ketemu Kos Idaman,
                        <span class="inline-block bg-gradient-to-r from-teal-300 via-emerald-300 to-orange-300 bg-clip-text text-transparent pb-1">Lebih Cepat & Gampang.</span>
                    </h1>

                    <p class="mt-4 max-w-xl text-base leading-relaxed text-slate-300 font-medium">
                        Tinggal buka peta, cek fasilitas, baca review jujur penyewa lain, langsung booking. Bayar dan kelola tagihan bulanan? Semua beres dari HP-mu!
                    </p>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row justify-center w-full">
                        <Link :href="route('public.kos.index')">
                            <Button class="group h-11 w-full rounded-full bg-teal-500 px-6 text-sm font-bold text-white shadow-md transition-all hover:bg-teal-400 hover:-translate-y-0.5 sm:w-auto">
                                Mulai Cari Kos Sekarang
                                <ArrowRight class="h-4 w-4 ml-2 transition-transform group-hover:translate-x-1" />
                            </Button>
                        </Link>
                        <a href="#cara-kerja">
                            <Button class="h-11 w-full rounded-full border border-slate-700 bg-slate-800 px-6 text-sm font-bold text-white transition-all hover:bg-slate-700 hover:border-slate-600 hover:-translate-y-0.5 sm:w-auto">
                                Lihat Cara Kerja
                            </Button>
                        </a>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm font-medium text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <Check class="h-4 w-4 text-teal-400" />
                            Pencarian peta
                        </span>
                        <span class="flex items-center gap-1.5">
                            <Check class="h-4 w-4 text-teal-400" />
                            Data ditinjau admin
                        </span>
                        <span class="flex items-center gap-1.5">
                            <Check class="h-4 w-4 text-teal-400" />
                            Pembayaran aman
                        </span>
                    </div>
                </div>

                <div class="relative min-h-[460px] lg:min-h-[650px] w-full">
                    <LandingHeroScene class="relative z-10" />
                </div>
            </div>

            <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-white via-white/80 to-transparent" />
        </section>



        <!-- 4. Animated map discovery -->
        <section id="peta-kos" class="scroll-mt-20 relative overflow-hidden bg-white py-16 sm:py-24 lg:py-0 lg:min-h-[85vh] flex flex-col justify-center border-y border-slate-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-[0.8fr_1.2fr] items-start gap-12 lg:gap-16">
                    <!-- Left: Text -->
                    <div data-reveal="left" class="max-w-xl lg:mt-6">
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Cari Kos Langsung di Peta. Gampang Banget!</h2>
                        <p class="mt-4 text-base leading-relaxed text-slate-600">
                            Males baca list kosan yang kepanjangan? Buka aja petanya, zoom in ke kampus atau area kantormu, dan pantau langsung kosan mana yang posisinya paling strategis buat kamu.
                        </p>
                        <Link :href="route('public.kos.index')" class="mt-8 inline-block">
                            <Button class="group h-12 rounded-full bg-[#0c292b] px-6 text-sm font-bold text-white shadow-md hover:bg-[#143b3e] transition-all hover:shadow-lg hover:-translate-y-0.5">
                                Cobain Petanya Sekarang
                                <ArrowRight class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </Button>
                        </Link>
                    </div>

                    <!-- Right: Map -->
                    <div data-reveal="right" class="relative">
                        <div class="relative rounded-[2rem] border border-slate-200 bg-slate-50 p-2 shadow-lg">
                            <LandingMapDiscovery :items="mapKos" class="overflow-hidden rounded-3xl w-full h-[350px] sm:h-[450px]" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. How it works -->
        <section id="cara-kerja" class="scroll-mt-20 bg-slate-900 py-24 sm:py-32 lg:py-0 lg:min-h-screen flex flex-col justify-center relative overflow-hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">Ngekos Tanpa Pusing, Cukup 4 Langkah.</h2>
                    <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-slate-300">Semuanya udah didesain simpel biar kamu gak ribet. Dari milih kamar sampai bayar sewa, semua selesai sambil rebahan.</p>
                </div>

                <div class="mt-20 max-w-6xl mx-auto relative">
                    <!-- Horizontal Timeline Track (Desktop) -->
                    <div class="hidden md:block absolute top-8 left-[10%] right-[10%] h-px bg-slate-800 z-0"></div>
                    <div class="hidden md:block absolute top-8 left-[10%] right-[10%] h-px bg-gradient-to-r from-teal-500/50 via-blue-500/50 to-violet-500/50 z-0"></div>

                    <!-- Vertical Timeline Track (Mobile) -->
                    <div class="md:hidden absolute left-8 top-8 bottom-8 w-px bg-gradient-to-b from-teal-500/50 via-blue-500/50 to-violet-500/50 z-0"></div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-12 md:gap-6 relative z-10">
                        <!-- Step 1 -->
                        <div data-reveal="scale" class="relative flex flex-row md:flex-col items-center md:text-center gap-6 md:gap-0 group" style="--reveal-delay: 0ms">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-slate-800 border border-slate-700 group-hover:border-teal-500 transition-all md:mb-6 group-hover:-translate-y-1">
                                <Search class="h-6 w-6 text-teal-400" />
                            </div>
                            <div class="flex-1 md:px-2">
                                <h3 class="text-lg sm:text-xl font-bold text-white mb-2 transition-colors">1. Kepoin Peta</h3>
                                <p class="text-sm leading-relaxed text-slate-400 transition-colors">Cari kosan favoritmu yang deket dari kampus atau tempat kerja.</p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div data-reveal="scale" class="relative flex flex-row md:flex-col items-center md:text-center gap-6 md:gap-0 group" style="--reveal-delay: 100ms">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-slate-800 border border-slate-700 group-hover:border-emerald-500 transition-all md:mb-6 group-hover:-translate-y-1">
                                <MapPin class="h-6 w-6 text-emerald-400" />
                            </div>
                            <div class="flex-1 md:px-2">
                                <h3 class="text-lg sm:text-xl font-bold text-white mb-2 transition-colors">2. Cek Detail Kamar</h3>
                                <p class="text-sm leading-relaxed text-slate-400 transition-colors">Lihat foto kamarnya, fasilitasnya apa aja, dan baca review asli dari anak kos lain.</p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div data-reveal="scale" class="relative flex flex-row md:flex-col items-center md:text-center gap-6 md:gap-0 group" style="--reveal-delay: 200ms">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-slate-800 border border-slate-700 group-hover:border-blue-500 transition-all md:mb-6 group-hover:-translate-y-1">
                                <ClipboardCheck class="h-6 w-6 text-blue-400" />
                            </div>
                            <div class="flex-1 md:px-2">
                                <h3 class="text-lg sm:text-xl font-bold text-white mb-2 transition-colors">3. Booking Sekali Klik</h3>
                                <p class="text-sm leading-relaxed text-slate-400 transition-colors">Udah sreg? Langsung pesen kamarnya lewat HP, gak usah panas-panasan nyamperin bapak kos.</p>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div data-reveal="scale" class="relative flex flex-row md:flex-col items-center md:text-center gap-6 md:gap-0 group" style="--reveal-delay: 300ms">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-slate-800 border border-slate-700 group-hover:border-violet-500 transition-all md:mb-6 group-hover:-translate-y-1">
                                <KeyRound class="h-6 w-6 text-violet-400" />
                            </div>
                            <div class="flex-1 md:px-2">
                                <h3 class="text-lg sm:text-xl font-bold text-white mb-2 transition-colors">4. Bayar & Huni</h3>
                                <p class="text-sm leading-relaxed text-slate-400 transition-colors">Selesaikan pembayaran tagihan bulanan dengan aman. Voila! Kamu tinggal bawa koper aja.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. Security & transparency -->
        <section id="keamanan" class="scroll-mt-20 bg-slate-50 py-20 sm:py-24 lg:py-0 lg:min-h-[85vh] flex flex-col justify-center relative overflow-hidden">
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px]" />
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div data-reveal class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Aman, Jujur, & Gak Ada Drama.</h2>
                    <p class="mt-4 text-base text-slate-600">
                        Semua data kos diverifikasi ketat sama tim kami. Mau baca review jujur dari anak kos lama? Atau mau laporin info kos yang zonk? Bisa banget!
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3 max-w-5xl mx-auto">
                    <!-- Feature 1 -->
                    <div data-reveal="scale" class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-md transition-shadow text-center group">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-teal-600 mb-6 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                            <ShieldCheck class="h-7 w-7" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Verifikasi Ketat</h3>
                        <p class="text-sm leading-relaxed text-slate-500">
                            Data properti dan dokumen legal ditinjau secara manual oleh tim kami sebelum kos dipublikasikan.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div data-reveal="scale" style="--reveal-delay: 100ms" class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-md transition-shadow text-center group">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-500 mb-6 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                            <Star class="h-7 w-7" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Ulasan Asli</h3>
                        <p class="text-sm leading-relaxed text-slate-500">
                            Baca opini dan penilaian nyata langsung dari sesama penyewa yang pernah menetap di sana.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div data-reveal="scale" style="--reveal-delay: 200ms" class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover:shadow-md transition-shadow text-center group">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-500 mb-6 group-hover:bg-rose-500 group-hover:text-white transition-colors">
                            <Flag class="h-7 w-7" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Laporan Cepat</h3>
                        <p class="text-sm leading-relaxed text-slate-500">
                            Sistem pelaporan yang efisien untuk menindaklanjuti properti atau pengguna bermasalah.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Tenancy and payment -->
        <section class="overflow-hidden bg-white py-16 sm:py-24 lg:py-0 lg:min-h-[85vh] flex flex-col justify-center border-y border-slate-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-10 lg:gap-16 lg:grid-cols-[0.9fr_1.1fr]">
                    <!-- Left: Simplified Mockup -->
                    <div data-reveal="left" class="order-2 lg:order-1 relative">
                        <div class="relative rounded-3xl bg-slate-50 p-6 sm:p-8 border border-slate-200 shadow-lg max-w-md mx-auto lg:ml-auto">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-5 mb-5">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Ringkasan</p>
                                    <p class="text-lg font-bold text-slate-900 mt-1">Invoice Bulanan</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700 border border-amber-200">Menunggu Pembayaran</span>
                            </div>

                            <div class="space-y-4">
                                <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-slate-100 shadow-sm transition-transform hover:-translate-y-1">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-teal-50 text-teal-600">
                                            <ReceiptText class="h-5 w-5" />
                                        </div>
                                        <span class="text-sm font-medium text-slate-700">Periode Sewa</span>
                                    </div>
                                    <span class="text-sm font-bold text-slate-900">Bulanan</span>
                                </div>
                                
                                <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-slate-100 shadow-sm transition-transform hover:-translate-y-1">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-500">
                                            <CreditCard class="h-5 w-5" />
                                        </div>
                                        <span class="text-sm font-medium text-slate-700">Metode</span>
                                    </div>
                                    <span class="text-sm font-bold text-slate-900">Sistem Otomatis</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Text Content -->
                    <div data-reveal="right" class="order-1 lg:order-2 max-w-xl">
                        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Bayar Sewa & Urus Tagihan Makin Santuy.</h2>
                        <p class="mt-4 text-base leading-relaxed text-slate-600">
                            Sistem kami memproses pembayaranmu secara otomatis. Tinggal bayar sesuai invoice, status langsung ter-update detik itu juga tanpa perlu konfirmasi panjang lebar!
                        </p>

                        <div class="mt-8 space-y-4">
                            <div class="flex gap-4 items-start">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-600"><Check class="h-4 w-4" /></span>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Invoice Terstruktur</p>
                                    <p class="text-sm text-slate-500 mt-0.5">Detail periode, jatuh tempo, dan nominal tersaji jelas.</p>
                                </div>
                            </div>
                            <div class="flex gap-4 items-start">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-600"><Check class="h-4 w-4" /></span>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Pembayaran 100% Otomatis</p>
                                    <p class="text-sm text-slate-500 mt-0.5">Tagihan langsung terbayar dan terkonfirmasi instan oleh sistem.</p>
                                </div>
                            </div>
                            <div class="flex gap-4 items-start">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-600"><Check class="h-4 w-4" /></span>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Riwayat Terarsip</p>
                                    <p class="text-sm text-slate-500 mt-0.5">Akses seluruh histori pembayaran sewa Anda kapan saja.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. WhatsApp notifications -->
        <section class="relative bg-slate-900 py-16 sm:py-24 lg:py-0 lg:min-h-[85vh] flex flex-col justify-center text-white overflow-hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid items-center gap-14 lg:gap-16 lg:grid-cols-[1.1fr_0.9fr]">
                    <div data-reveal="left" class="max-w-xl">
                        <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl text-white">Selalu Update Langsung ke WhatsApp-mu.</h2>
                        <p class="mt-4 text-base leading-relaxed text-slate-300">
                            Gak perlu rajin-rajin buka web cuma buat ngecek status kamar. Mulai dari reminder tagihan sampai konfirmasi bayar, robot WA kami bakal langsung nge-chat kamu!
                        </p>

                        <div class="mt-8 space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-500/10 text-teal-400"><Check class="h-4 w-4" /></span>
                                <span class="text-sm font-semibold text-slate-200">Pengajuan Sewa</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-500/10 text-teal-400"><Check class="h-4 w-4" /></span>
                                <span class="text-sm font-semibold text-slate-200">Pengingat Invoice</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-teal-500/10 text-teal-400"><Check class="h-4 w-4" /></span>
                                <span class="text-sm font-semibold text-slate-200">Status Pembayaran</span>
                            </div>
                        </div>
                    </div>

                    <div data-reveal="right" class="relative mx-auto w-full max-w-md lg:ml-auto">
                        <div class="relative rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm shadow-xl">
                            <div class="mb-5 flex items-center gap-4 border-b border-white/10 pb-5">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#25D366] shadow-md">
                                    <MessageCircle class="h-6 w-6 text-white" />
                                </div>
                                <div>
                                    <p class="text-base font-bold text-white">Kos Online Bot</p>
                                    <p class="flex items-center gap-1.5 text-xs text-teal-300"><span class="h-1.5 w-1.5 rounded-full bg-[#25D366]"></span> Otomatis 24/7</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="max-w-[90%] rounded-2xl rounded-tl-sm bg-white p-4 text-slate-800 shadow-sm">
                                    <p class="text-sm leading-relaxed">Halo! Tagihan sewa kamar <strong>A-01</strong> kamu sudah terbit. Yuk klik link berikut untuk membayar otomatis: <br><span class="text-teal-600 font-medium break-all">kosonline.id/inv/8923</span></p>
                                    <p class="mt-2 text-right text-[10px] font-bold text-slate-400">09.15</p>
                                </div>
                                <div class="ml-auto max-w-[90%] rounded-2xl rounded-tr-sm bg-[#dcf8c6] p-4 text-slate-800 shadow-sm">
                                    <p class="text-sm leading-relaxed font-medium">Sip, tagihannya udah langsung aku bayar lewat link itu ya. Makasih!</p>
                                    <p class="mt-2 flex items-center justify-end gap-1 text-[10px] font-bold text-teal-700">09.18 <CheckCheck class="h-3.5 w-3.5 text-blue-500" /></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 10. Owner solution -->
        <section id="pemilik" class="scroll-mt-20 bg-slate-50 py-16 sm:py-24 lg:py-0 lg:min-h-[85vh] flex flex-col justify-center border-y border-slate-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div data-reveal class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Punya Kos-Kosan? Atur Semuanya Dari Satu Layar.</h2>
                    <p class="mt-4 text-base leading-relaxed text-slate-600">
                        Ucapkan selamat tinggal pada buku catatan lecek! Promosikan kamar kosong, pantau tagihan anak kos, sampai tarik saldo pendapatan cukup dari satu dashboard cerdas.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4 mb-12">
                    <div data-reveal="scale" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-600 mb-5">
                            <Building2 class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">Manajemen Properti</h3>
                        <p class="text-sm leading-relaxed text-slate-500">Atur profil, foto, fasilitas, kamar, dan harga sewa dengan mudah.</p>
                    </div>
                    
                    <div data-reveal="scale" style="--reveal-delay: 100ms" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 mb-5">
                            <FileCheck2 class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">Validasi Legalitas</h3>
                        <p class="text-sm leading-relaxed text-slate-500">Unggah dokumen resmi untuk dapatkan lencana terverifikasi.</p>
                    </div>

                    <div data-reveal="scale" style="--reveal-delay: 200ms" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 mb-5">
                            <BarChart3 class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">Pantau Pembayaran</h3>
                        <p class="text-sm leading-relaxed text-slate-500">Lacak penyewa aktif dan konfirmasi invoice secara otomatis.</p>
                    </div>

                    <div data-reveal="scale" style="--reveal-delay: 300ms" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 mb-5">
                            <WalletCards class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">Dompet Digital</h3>
                        <p class="text-sm leading-relaxed text-slate-500">Terima pembayaran dan cairkan saldo langsung ke rekening Anda.</p>
                    </div>
                </div>

                <div data-reveal class="flex justify-center">
                    <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')">
                        <Button class="group h-12 rounded-full bg-slate-900 px-8 text-sm font-bold text-white shadow-md hover:bg-slate-800 transition-all hover:-translate-y-0.5">
                            Mulai Jadi Mitra
                            <ArrowRight class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </Button>
                    </Link>
                    <Link v-else-if="$page.props.auth.user?.role === 'admin'" :href="route('admin.dashboard')">
                        <Button class="group h-12 rounded-full bg-slate-900 px-8 text-sm font-bold text-white shadow-md hover:bg-slate-800 transition-all hover:-translate-y-0.5">
                            Buka Dashboard Pemilik
                            <ArrowRight class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- 11. Tenant reviews -->
        <section id="ulasan" class="scroll-mt-20 bg-white py-16 sm:py-24 lg:py-0 lg:min-h-[85vh] flex flex-col justify-center relative overflow-hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div data-reveal class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Kata Mereka yang Udah Buktiin Langsung.</h2>
                    <p class="mt-4 text-base leading-relaxed text-slate-600">
                        Masih ragu? Dengerin nih cerita jujur dari para penyewa lain yang udah ngerasain gampangnya dapet hunian nyaman lewat platform kami.
                    </p>
                </div>

                <div v-if="featuredReviews.length" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 mb-12">
                    <article
                        v-for="(review, index) in featuredReviews"
                        :key="review.id"
                        data-reveal="scale"
                        class="group relative rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md"
                        :style="{ '--reveal-delay': `${(index % 3) * 90}ms` }"
                    >
                        <div class="absolute right-6 top-6 opacity-5 transition-opacity group-hover:opacity-10 text-teal-600">
                            <Quote class="h-10 w-10" />
                        </div>
                        <div class="flex gap-1 mb-4" :aria-label="`${review.rating} dari 5 bintang`">
                            <Star
                                v-for="score in 5"
                                :key="score"
                                class="h-4 w-4"
                                :class="score <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-200'"
                            />
                        </div>

                        <p class="line-clamp-4 text-sm leading-relaxed text-slate-600 italic">â€œ{{ review.comment }}â€</p>

                        <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-50 text-sm font-bold text-teal-700">
                                {{ initials(review.user?.name) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900">{{ review.user?.name }}</p>
                                <p class="truncate text-xs font-medium text-slate-500">{{ review.boarding_house?.name }}</p>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-6 py-16 text-center mb-12">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-sm mb-4">
                        <MessageCircle class="h-6 w-6 text-slate-400" />
                    </div>
                    <h3 class="font-bold text-lg text-slate-800">Belum ada ulasan yang dipublikasikan</h3>
                    <p class="mt-2 text-sm text-slate-500 max-w-md mx-auto">Ulasan penyewa akan muncul di sini setelah mereka membagikan pengalaman pada detail kos.</p>
                </div>

                <div data-reveal class="flex justify-center">
                    <Link :href="route('public.kos.index')">
                        <Button class="group h-12 rounded-full bg-slate-900 px-8 text-sm font-bold text-white shadow-md hover:bg-slate-800 transition-all hover:-translate-y-0.5">
                            Jelajahi Semua Kos
                            <ArrowRight class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- 12. FAQ -->
        <section class="bg-slate-50 py-16 sm:py-24 lg:py-0 lg:min-h-[85vh] flex flex-col justify-center border-y border-slate-100 relative">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div data-reveal class="mx-auto max-w-2xl text-center mb-16">
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Masih Punya Pertanyaan? (FAQ)</h2>
                    <p class="mt-4 text-base leading-relaxed text-slate-600">
                        Kumpulin semua rasa kepomu di sini. Mulai dari cara booking, metode pembayaran, sampai cara daftar jadi pemilik kos udah kami rangkum semua jawabannya.
                    </p>
                </div>

                <div data-reveal="scale" class="mx-auto max-w-3xl space-y-4">
                    <details v-for="faq in faqs" :key="faq.question" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all open:border-teal-200 open:shadow-md hover:border-slate-300">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-5 font-bold text-slate-900 outline-none">
                            <span class="text-base">{{ faq.question }}</span>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition-transform group-open:rotate-180 group-open:bg-teal-50 group-open:text-teal-600">
                                <ChevronDown class="h-4 w-4" />
                            </span>
                        </summary>
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 pr-8 pt-4 border-t border-slate-100">{{ faq.answer }}</p>
                    </details>
                </div>
            </div>
        </section>

        <!-- 13. Final CTA -->
        <section class="bg-white px-4 py-16 sm:px-6 sm:py-24 lg:py-0 lg:min-h-[70vh] flex flex-col justify-center lg:px-8">
            <div data-reveal="scale" class="mx-auto max-w-5xl overflow-hidden rounded-3xl bg-slate-900 px-6 py-16 text-center shadow-xl sm:px-12 sm:py-20 relative">
                <div class="absolute inset-0 bg-slate-800/20 mix-blend-overlay" />
                
                <div class="relative z-10">
                    <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl text-white">
                        Tunggu Apa Lagi? Yuk Mulai Sekarang!
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-slate-300">
                        Gak usah ditunda-tunda. Cari kosan idamanmu sekarang, atau gabung jadi pemilik kos buat nikmatin gampangnya ngurus properti secara online.
                    </p>

                    <div class="mt-10 flex flex-col justify-center gap-4 sm:flex-row">
                        <Link :href="route('public.kos.index')">
                            <Button class="group h-12 w-full rounded-full bg-teal-500 px-8 text-sm font-bold text-white shadow-md transition-all hover:bg-teal-400 sm:w-auto hover:-translate-y-0.5">
                                Mulai Cari Kos
                                <Search class="h-4 w-4 ml-2 transition-transform group-hover:rotate-12" />
                            </Button>
                        </Link>
                        <Link v-if="canRegister && !$page.props.auth.user" :href="route('register')">
                            <Button class="group h-12 w-full rounded-full border border-slate-700 bg-slate-800 px-8 text-sm font-bold text-white transition-all hover:bg-slate-700 hover:border-slate-600 sm:w-auto hover:-translate-y-0.5">
                                Daftarkan Kos Anda
                                <Building2 class="h-4 w-4 ml-2 transition-transform group-hover:-translate-y-1" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- 14. Professional footer -->
        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-1">
                        <Link :href="route('home')" class="inline-flex items-center gap-2 text-lg font-bold text-slate-900">
                            <img v-if="$page.props.appSettings?.app_logo" :src="'/storage/' + $page.props.appSettings.app_logo" class="w-8 h-8 object-contain" alt="Logo" />
                            <span v-else class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                                <Home class="h-4 w-4" />
                            </span>
                            {{ $page.props.appSettings?.app_name || 'Kos Online' }}
                        </Link>
                        <p class="mt-4 text-sm leading-relaxed text-slate-500">
                            {{ $page.props.appSettings?.footer_text || 'Platform pencarian dan pengelolaan kos yang mempertemukan kebutuhan penyewa dan pemilik dalam alur yang lebih tertata.' }}
                        </p>
                        
                        <div class="mt-4 space-y-2">
                            <a v-if="$page.props.appSettings?.contact_email" :href="'mailto:' + $page.props.appSettings.contact_email" class="flex items-center gap-2 text-sm text-slate-500 hover:text-teal-600 transition-colors w-fit">
                                <Mail class="w-4 h-4" />
                                {{ $page.props.appSettings.contact_email }}
                            </a>
                            <a v-if="$page.props.appSettings?.contact_phone" :href="'https://wa.me/' + $page.props.appSettings.contact_phone.replace(/\D/g,'')" class="flex items-center gap-2 text-sm text-slate-500 hover:text-teal-600 transition-colors w-fit">
                                <Phone class="w-4 h-4" />
                                {{ $page.props.appSettings.contact_phone }}
                            </a>
                        </div>
                        
                        <div class="mt-6 flex gap-4 text-sm font-medium">
                            <a v-if="$page.props.appSettings?.link_instagram" :href="$page.props.appSettings.link_instagram" target="_blank" class="text-slate-400 hover:text-teal-600 transition-colors">Instagram</a>
                            <a v-if="$page.props.appSettings?.link_facebook" :href="$page.props.appSettings.link_facebook" target="_blank" class="text-slate-400 hover:text-teal-600 transition-colors">Facebook</a>
                            <a v-if="$page.props.appSettings?.link_tiktok" :href="$page.props.appSettings.link_tiktok" target="_blank" class="text-slate-400 hover:text-teal-600 transition-colors">TikTok</a>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Jelajahi</h3>
                        <nav class="mt-4 flex flex-col gap-3 text-sm text-slate-500">
                            <Link :href="route('public.kos.index')" class="hover:text-teal-600 transition-colors">Cari Kos</Link>
                            <a href="#cara-kerja" class="hover:text-teal-600 transition-colors">Cara Kerja</a>
                            <a href="#ulasan" class="hover:text-teal-600 transition-colors">Ulasan Penyewa</a>
                        </nav>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Platform & Legal</h3>
                        <nav class="mt-4 flex flex-col gap-3 text-sm text-slate-500">
                            <a href="#keamanan" class="hover:text-teal-600 transition-colors">Keamanan</a>
                            <a href="#pemilik" class="hover:text-teal-600 transition-colors">Untuk Pemilik</a>
                            <Link v-if="canLogin && !$page.props.auth.user" :href="route('login')" class="hover:text-teal-600 transition-colors">Masuk Akun</Link>
                            <Link :href="route('page.show', 'tentang-kami')" class="hover:text-teal-600 transition-colors">Tentang Kami</Link>
                            <Link :href="route('page.show', 'syarat-ketentuan')" class="hover:text-teal-600 transition-colors">Syarat & Ketentuan</Link>
                            <Link :href="route('page.show', 'kebijakan-privasi')" class="hover:text-teal-600 transition-colors">Kebijakan Privasi</Link>
                        </nav>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Akses Cepat</h3>
                        <p class="mt-4 text-sm leading-relaxed text-slate-500">Temukan kos yang tersedia di sekitar area tujuan Anda.</p>
                        <Link :href="route('public.kos.index')" class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-teal-600 hover:text-teal-700 transition-colors">
                            Buka peta kos
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>

                <div class="mt-12 flex flex-col gap-4 border-t border-slate-100 pt-8 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ new Date().getFullYear() }} {{ $page.props.appSettings?.app_name || 'Kos Online' }}. Seluruh hak dilindungi.</p>
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
