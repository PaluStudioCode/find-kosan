<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import { Map, ShieldCheck, CreditCard, ArrowRight, Star, Sparkles, Navigation } from 'lucide-vue-next';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <PublicLayout>
        <Head title="Platform Kos Era Baru" />

        <!-- Next-Gen Hero Section -->
        <section class="relative min-h-[90vh] flex items-center justify-center overflow-hidden bg-[#0A0F1C]">
            <!-- Dynamic Background Elements -->
            <div class="absolute inset-0 w-full h-full">
                <div class="absolute top-1/4 left-1/4 w-[500px] h-[500px] bg-indigo-600/30 rounded-full blur-[120px] mix-blend-screen animate-pulse"></div>
                <div class="absolute bottom-1/4 right-1/4 w-[600px] h-[600px] bg-blue-500/20 rounded-full blur-[150px] mix-blend-screen"></div>
                
                <!-- Grid overlay -->
                <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff0a_1px,transparent_1px),linear-gradient(to_bottom,#ffffff0a_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_60%_at_50%_50%,#000_70%,transparent_100%)]"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full flex flex-col lg:flex-row items-center gap-12 lg:gap-20">
                
                <!-- Hero Text Content -->
                <div class="w-full lg:w-1/2 flex flex-col items-center lg:items-start text-center lg:text-left pt-12 lg:pt-0">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 backdrop-blur-md mb-8">
                        <Sparkles class="w-4 h-4 text-indigo-400" />
                        <span class="text-sm font-medium text-indigo-100 tracking-wide">Pencarian Kos Cerdas Berbasis Lokasi</span>
                    </div>
                    
                    <h1 class="text-5xl lg:text-7xl font-extrabold text-white tracking-tight leading-[1.1] mb-8">
                        Cari Kos Kini<br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-blue-400 to-cyan-300">
                            Lebih Intuitif.
                        </span>
                    </h1>
                    
                    <p class="text-lg text-gray-400 mb-10 max-w-xl leading-relaxed font-light">
                        Lupakan cara lama mencari kos. Sistem kami menggunakan pelacakan GPS *real-time* untuk menampilkan properti terbaik tepat di sekitar Anda, secara instan.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-4">
                        <Link :href="route('public.kos.index')">
                            <Button class="w-full sm:w-auto h-14 px-8 bg-indigo-600 hover:bg-indigo-500 text-white rounded-full text-lg font-semibold shadow-[0_0_40px_-10px_rgba(79,70,229,0.5)] transition-all hover:scale-105 group">
                                Buka Peta Kos <Map class="w-5 h-5 ml-2 group-hover:rotate-12 transition-transform" />
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Interactive Visual / Bento Box -->
                <div class="w-full lg:w-1/2 relative h-[500px] lg:h-[600px] flex items-center justify-center">
                    
                    <!-- Floating Main Card (Map Simulation) -->
                    <div class="absolute inset-x-4 lg:inset-x-10 h-[400px] bg-white/[0.03] border border-white/10 rounded-3xl backdrop-blur-xl shadow-2xl overflow-hidden transform lg:rotate-2 hover:rotate-0 transition-transform duration-700">
                        <!-- Header fake -->
                        <div class="h-12 border-b border-white/10 flex items-center px-4 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                            <div class="ml-4 h-4 w-32 bg-white/10 rounded-full"></div>
                        </div>
                        
                        <!-- Map Content Fake -->
                        <div class="relative w-full h-full bg-[#0F172A] overflow-hidden p-6">
                            <!-- Radar pulse effect -->
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                <div class="w-64 h-64 bg-indigo-500/20 rounded-full animate-ping [animation-duration:3s]"></div>
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 bg-indigo-400 rounded-full ring-4 ring-indigo-400/30"></div>
                            </div>

                            <!-- Mock Markers -->
                            <div class="absolute top-1/4 left-1/4 bg-white p-2 rounded-xl shadow-xl transform hover:scale-110 transition-transform cursor-pointer animate-[bounce_4s_infinite]">
                                <div class="w-32 h-20 bg-gray-200 rounded-lg mb-2 overflow-hidden relative">
                                    <div class="absolute inset-0 bg-gradient-to-tr from-blue-400 to-indigo-500 opacity-80"></div>
                                </div>
                                <div class="h-3 w-20 bg-gray-800 rounded-full mb-1"></div>
                                <div class="h-2 w-12 bg-gray-400 rounded-full"></div>
                            </div>

                            <div class="absolute bottom-1/3 right-1/4 bg-white p-2 rounded-xl shadow-xl transform hover:scale-110 transition-transform cursor-pointer animate-[bounce_5s_infinite_reverse]">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <Home class="w-5 h-5 text-indigo-600" />
                                    </div>
                                    <div>
                                        <div class="h-3 w-16 bg-gray-800 rounded-full mb-1"></div>
                                        <div class="h-2 w-10 bg-gray-400 rounded-full"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Feature Card 1 -->
                    <div class="absolute -bottom-6 -left-4 lg:-left-12 bg-white/10 backdrop-blur-xl border border-white/20 p-5 rounded-2xl shadow-[0_20px_50px_-15px_rgba(0,0,0,0.5)] transform -rotate-3 hover:scale-105 transition-all z-20">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center">
                                <ShieldCheck class="w-6 h-6 text-green-400" />
                            </div>
                            <div>
                                <p class="text-white font-bold text-lg">100% Aman</p>
                                <p class="text-gray-400 text-sm">Properti Terverifikasi</p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Feature Card 2 -->
                    <div class="absolute top-10 -right-4 lg:-right-8 bg-white/10 backdrop-blur-xl border border-white/20 p-5 rounded-2xl shadow-[0_20px_50px_-15px_rgba(0,0,0,0.5)] transform rotate-6 hover:scale-105 transition-all z-20">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center">
                                <Navigation class="w-6 h-6 text-blue-400" />
                            </div>
                            <div>
                                <p class="text-white font-bold text-lg">GPS Real-time</p>
                                <p class="text-gray-400 text-sm">Pemindaian Radius</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
            <!-- Bottom Fade for transition -->
            <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-gray-50 to-transparent z-20"></div>
        </section>

        <!-- Bento Grid Features Section -->
        <section class="py-24 bg-gray-50 relative z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center mb-20">
                    <h2 class="text-4xl font-extrabold text-gray-900 mb-6">Ekosistem Sewa Modern</h2>
                    <p class="text-xl text-gray-500 max-w-3xl mx-auto">Dirancang khusus untuk gaya hidup serba cepat. Tidak ada lagi penipuan, survei lokasi tanpa hasil, atau pembayaran tunai yang merepotkan.</p>
                </div>
                
                <!-- Bento Grid Layout -->
                <div class="grid grid-cols-1 md:grid-cols-3 md:grid-rows-2 gap-6 h-auto md:h-[600px]">
                    
                    <!-- Large Feature -->
                    <div class="md:col-span-2 bg-white rounded-3xl p-8 lg:p-12 border border-gray-100 shadow-xl shadow-gray-200/40 relative overflow-hidden group">
                        <div class="relative z-10 w-full md:w-2/3">
                            <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center mb-6">
                                <Map class="w-7 h-7 text-indigo-600" />
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Pemindaian Peta Interaktif</h3>
                            <p class="text-gray-600 leading-relaxed text-lg mb-8">
                                Teknologi kami membaca koordinat Anda dan memunculkan ketersediaan kos di sekitar Anda secara langsung. Geser radius pencarian untuk memperluas jangkauan tanpa mengetik satu huruf pun.
                            </p>
                            <Link :href="route('public.kos.index')" class="inline-flex items-center text-indigo-600 font-semibold hover:text-indigo-700">
                                Cobalah Sekarang <ArrowRight class="w-4 h-4 ml-1 group-hover:translate-x-2 transition-transform" />
                            </Link>
                        </div>
                        
                        <div class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/4 opacity-20 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none">
                            <Map class="w-96 h-96 text-indigo-100" />
                        </div>
                    </div>

                    <!-- Small Feature 1 -->
                    <div class="bg-gray-900 rounded-3xl p-8 border border-gray-800 shadow-xl relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center mb-6">
                                <CreditCard class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3">Transaksi Digital</h3>
                            <p class="text-gray-400">
                                Integrasi pembayaran otomatis. Lakukan booking, bayar sewa, hingga perpanjangan hanya dalam 3 klik.
                            </p>
                        </div>
                        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-purple-500/20 blur-3xl rounded-full"></div>
                    </div>

                    <!-- Small Feature 2 -->
                    <div class="bg-indigo-600 rounded-3xl p-8 shadow-xl relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-black/20 rounded-xl flex items-center justify-center mb-6">
                                <ShieldCheck class="w-6 h-6 text-white" />
                            </div>
                            <h3 class="text-xl font-bold text-white mb-3">Anti Penipuan</h3>
                            <p class="text-indigo-100">
                                Identitas pemilik dan legalitas kos divalidasi langsung oleh tim internal sebelum dipublikasikan.
                            </p>
                        </div>
                        <div class="absolute -top-10 -left-10 w-40 h-40 bg-white/10 blur-2xl rounded-full"></div>
                    </div>

                    <!-- Horizontal Feature -->
                    <div class="md:col-span-2 bg-white rounded-3xl p-8 border border-gray-100 shadow-xl shadow-gray-200/40 flex flex-col md:flex-row items-center justify-between gap-8">
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-bold mb-4">
                                <Star class="w-3 h-3 fill-green-700" /> Reputasi Terbaik
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Penyewa & Pemilik Dalam Satu Atap</h3>
                            <p class="text-gray-600 max-w-md">Kami membangun platform yang memudahkan penyewa mencari tempat tinggal, sekaligus memberdayakan pemilik kos dalam manajemen properti cerdas.</p>
                        </div>
                        <Link v-if="!$page.props.auth.user" :href="route('register')" class="shrink-0">
                            <Button class="rounded-full bg-gray-900 hover:bg-black h-12 px-8">Mulai Daftar</Button>
                        </Link>
                    </div>

                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="py-24 bg-white text-center">
            <h2 class="text-3xl md:text-5xl font-extrabold tracking-tight text-gray-900 mb-8">Ubah Cara Anda Mencari Tempat Tinggal</h2>
            <Link :href="route('public.kos.index')">
                <Button size="lg" class="h-16 px-10 text-xl bg-indigo-600 hover:bg-indigo-700 rounded-full shadow-[0_10px_40px_-10px_rgba(79,70,229,0.5)] transform hover:scale-105 transition-all">
                    Buka Peta Interaktif
                </Button>
            </Link>
        </section>

    </PublicLayout>
</template>
