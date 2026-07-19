<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Search, Map, ShieldCheck, CreditCard, Home, MapPin, CheckCircle, ArrowRight, Star } from 'lucide-vue-next';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const searchQuery = ref('');

const search = () => {
    if (searchQuery.value) {
        router.get(route('public.kos.index'), { search: searchQuery.value });
    } else {
        router.get(route('public.kos.index'));
    }
};
</script>

<template>
    <PublicLayout>
        <Head title="Temukan Kos Impianmu" />

        <!-- Hero Section -->
        <section class="relative bg-gradient-to-br from-primary/90 to-blue-900 text-white overflow-hidden">
            <!-- Abstract background patterns -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-white blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-[500px] h-[500px] rounded-full bg-blue-300 blur-3xl"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32 flex flex-col items-center text-center">
                <span class="px-4 py-1.5 rounded-full bg-white/20 text-white/90 backdrop-blur-sm text-sm font-semibold mb-6 border border-white/20 shadow-sm flex items-center gap-2">
                    <Star class="w-4 h-4 text-yellow-300 fill-yellow-300" /> Platform Kos #1 di Indonesia
                </span>
                <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight mb-6 leading-tight">
                    Temukan Kos Nyaman,<br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-indigo-100">Tanpa Ribet.</span>
                </h1>
                <p class="text-lg md:text-xl text-blue-100 max-w-2xl mb-10 leading-relaxed">
                    Sistem penyewaan kos yang transparan, aman, dan terverifikasi. 
                    Mulai dari pencarian hingga pembayaran, semua dalam genggaman Anda.
                </p>
                
                <!-- Search Box -->
                <div class="w-full max-w-3xl bg-white/10 p-2 md:p-3 rounded-2xl backdrop-blur-md shadow-2xl border border-white/20 flex flex-col md:flex-row gap-2 transition-all hover:bg-white/15">
                    <div class="relative flex-grow">
                        <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <Input 
                            v-model="searchQuery" 
                            @keyup.enter="search"
                            placeholder="Cari nama kos, kota, atau area..." 
                            class="h-14 pl-12 pr-4 w-full bg-white rounded-xl text-gray-900 placeholder:text-gray-400 border-0 focus-visible:ring-2 focus-visible:ring-primary text-lg" 
                        />
                    </div>
                    <Button @click="search" size="lg" class="h-14 px-8 rounded-xl bg-primary hover:bg-primary/90 text-lg font-semibold shadow-lg shrink-0">
                        <Search class="w-5 h-5 mr-2" /> Cari Kos
                    </Button>
                </div>
                
                <div class="mt-8 flex items-center justify-center flex-wrap gap-6 text-sm font-medium text-blue-200">
                    <div class="flex items-center"><CheckCircle class="w-4 h-4 mr-1 text-green-400" /> 100+ Kos Terverifikasi</div>
                    <div class="flex items-center"><CheckCircle class="w-4 h-4 mr-1 text-green-400" /> Pembayaran Aman</div>
                    <div class="flex items-center"><CheckCircle class="w-4 h-4 mr-1 text-green-400" /> Dukungan 24/7</div>
                </div>
            </div>
        </section>

        <!-- Value Proposition Section -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Mengapa Memilih Kami?</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto text-lg">Kami menghadirkan pengalaman mencari dan menyewa kos yang jauh lebih baik, mengutamakan keamanan dan kenyamanan Anda.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <Card class="border-none shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 bg-white">
                        <CardHeader>
                            <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-4">
                                <ShieldCheck class="w-7 h-7 text-blue-600" />
                            </div>
                            <CardTitle class="text-xl">Terverifikasi & Aman</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-gray-600 leading-relaxed">
                                Setiap properti yang tampil telah melewati proses verifikasi ketat oleh tim admin kami. Dokumen legalitas terjamin sehingga terhindar dari penipuan.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Feature 2 -->
                    <Card class="border-none shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 bg-white">
                        <CardHeader>
                            <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center mb-4">
                                <Map class="w-7 h-7 text-green-600" />
                            </div>
                            <CardTitle class="text-xl">Informasi Super Lengkap</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-gray-600 leading-relaxed">
                                Dilengkapi dengan galeri foto beresolusi tinggi, daftar fasilitas terperinci per kamar, dan integrasi titik koordinat peta yang sangat akurat.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Feature 3 -->
                    <Card class="border-none shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 bg-white">
                        <CardHeader>
                            <div class="w-14 h-14 bg-purple-100 rounded-2xl flex items-center justify-center mb-4">
                                <CreditCard class="w-7 h-7 text-purple-600" />
                            </div>
                            <CardTitle class="text-xl">Pembayaran Digital Instan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-gray-600 leading-relaxed">
                                Lakukan pembayaran uang sewa atau DP langsung melalui aplikasi. Mendukung transfer bank otomatis dan pindai QRIS untuk kemudahan transaksi.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="py-24 bg-white border-y border-gray-100 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center gap-16">
                    <div class="md:w-1/2">
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Cara Kerja yang Sangat Mudah</h2>
                        <div class="space-y-8 mt-10">
                            <div class="flex gap-4">
                                <div class="shrink-0 w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xl shadow-md">1</div>
                                <div>
                                    <h4 class="text-xl font-semibold text-gray-900 mb-2">Cari Kos Impian</h4>
                                    <p class="text-gray-600">Gunakan fitur pencarian cerdas kami untuk menemukan kos yang sesuai dengan kriteria lokasi, jarak, dan *budget* Anda.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="shrink-0 w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xl shadow-md">2</div>
                                <div>
                                    <h4 class="text-xl font-semibold text-gray-900 mb-2">Pilih Kamar & Pesan</h4>
                                    <p class="text-gray-600">Lihat ketersediaan kamar secara real-time, pilih periode sewa yang Anda inginkan, dan ajukan pemesanan langsung ke pemilik kos.</p>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="shrink-0 w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xl shadow-md">3</div>
                                <div>
                                    <h4 class="text-xl font-semibold text-gray-900 mb-2">Bayar & Tempati</h4>
                                    <p class="text-gray-600">Setelah disetujui, bayar tagihan dengan aman. Anda akan menerima bukti pembayaran sah dan siap untuk memindahkan barang Anda!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="md:w-1/2 w-full relative">
                        <!-- Abstract illustration -->
                        <div class="aspect-square bg-gradient-to-tr from-blue-100 to-indigo-50 rounded-full p-8 relative shadow-inner border border-blue-50/50 flex items-center justify-center w-full max-w-md mx-auto">
                            <Home class="w-40 h-40 text-blue-200" />
                            
                            <!-- Floating UI elements to simulate app interaction -->
                            <div class="absolute top-12 -left-4 md:-left-12 bg-white p-4 rounded-xl shadow-xl border border-gray-100 flex items-center gap-3 animate-[bounce_4s_infinite]">
                                <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center"><CheckCircle class="w-5 h-5" /></div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">Status Pemesanan</p>
                                    <p class="text-sm font-bold text-gray-900">Disetujui Pemilik</p>
                                </div>
                            </div>
                            
                            <div class="absolute bottom-16 -right-4 md:-right-8 bg-white p-4 rounded-xl shadow-xl border border-gray-100 flex items-center gap-3 animate-[bounce_5s_infinite_reverse]">
                                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center"><CreditCard class="w-5 h-5" /></div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium">Pembayaran QRIS</p>
                                    <p class="text-sm font-bold text-gray-900">Rp 1.500.000 Berhasil</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action -->
        <section class="py-20 bg-gray-900 text-white relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiNmZmYiLz48L3N2Zz4=')]"></div>
            <div class="relative max-w-4xl mx-auto px-4 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-6 tracking-tight">Siap Menemukan Kos Baru Anda?</h2>
                <p class="text-xl text-gray-400 mb-10">Ribuan kamar kos pilihan dengan fasilitas terbaik siap untuk Anda huni hari ini juga.</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <Link :href="route('public.kos.index')">
                        <Button size="lg" class="w-full sm:w-auto h-14 px-8 text-lg bg-primary hover:bg-primary/90 rounded-full shadow-lg">
                            Eksplorasi Semua Kos <ArrowRight class="w-5 h-5 ml-2" />
                        </Button>
                    </Link>
                    <template v-if="!$page.props.auth.user">
                        <Link :href="route('register')">
                            <Button variant="outline" size="lg" class="w-full sm:w-auto h-14 px-8 text-lg rounded-full text-gray-900 hover:text-gray-900 bg-white hover:bg-gray-100 border-none shadow-lg">
                                Daftar Sebagai Penyewa
                            </Button>
                        </Link>
                    </template>
                </div>
            </div>
        </section>

    </PublicLayout>
</template>
