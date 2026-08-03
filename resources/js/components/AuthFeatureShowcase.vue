<script setup>
import { Home, ShieldCheck, CreditCard, Star, MapPin } from 'lucide-vue-next';
</script>

<template>
    <div class="relative flex h-full w-full items-center justify-center pb-24 lg:pb-32 xl:pb-40 overflow-hidden bg-slate-900">
        <!-- Animated Background Glows -->
        <div class="absolute -top-[20%] -left-[10%] h-[70%] w-[70%] rounded-full bg-teal-600/40 mix-blend-screen blur-[120px] animate-[pulse_8s_ease-in-out_infinite]"></div>
        <div class="absolute top-[40%] -right-[20%] h-[60%] w-[60%] rounded-full bg-indigo-600/40 mix-blend-screen blur-[120px] animate-[pulse_10s_ease-in-out_infinite_reverse]"></div>
        <div class="absolute -bottom-[20%] left-[20%] h-[50%] w-[50%] rounded-full bg-amber-500/20 mix-blend-screen blur-[100px] animate-[pulse_6s_ease-in-out_infinite]"></div>

        <!-- Floating UI Collage Wrapper (CSS 3D Transform) -->
        <div class="relative z-10 grid w-full max-w-[350px] lg:max-w-[420px] xl:max-w-[500px] grid-cols-2 gap-4 xl:gap-6 p-4 xl:p-8 scale-90 lg:scale-95 xl:scale-100" style="transform: perspective(1000px) rotateX(12deg) rotateY(-18deg) rotateZ(6deg);">
            
            <!-- Left Column -->
            <div class="space-y-4 xl:space-y-6 pt-12 animate-[float_6s_ease-in-out_infinite]">
                
                <!-- Mock Card 1: Notification -->
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-md shadow-[0_20px_40px_rgba(0,0,0,0.4)]">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-500/20 text-teal-300">
                            <ShieldCheck class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white">Transaksi Aman</p>
                            <p class="text-xs text-slate-300">Terverifikasi sistem</p>
                        </div>
                    </div>
                </div>

                <!-- Mock Card 2: Property Component -->
                <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5 backdrop-blur-md shadow-[0_20px_40px_rgba(0,0,0,0.4)]">
                    <div class="h-32 w-full bg-slate-800/80 bg-[url('https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&q=80')] bg-cover bg-center mix-blend-luminosity opacity-70"></div>
                    <div class="p-4">
                        <h3 class="text-base font-bold text-white mb-1">Kos Premium Jakarta</h3>
                        <p class="text-xs text-slate-300 flex items-center gap-1 mb-3">
                            <MapPin class="w-3 h-3" /> Jakarta Selatan
                        </p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-extrabold text-teal-400">Rp 2.5 Juta</span>
                            <div class="flex items-center gap-1 text-amber-400 text-xs font-bold">
                                <Star class="w-3 h-3 fill-amber-400" /> 4.9
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="space-y-4 xl:space-y-6 animate-[float_7s_ease-in-out_infinite_reverse]">
                
                <!-- Mock Card 3: Payment -->
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-md shadow-[0_20px_40px_rgba(0,0,0,0.4)]">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-300">
                                <CreditCard class="h-4 w-4" />
                            </div>
                            <span class="text-xs font-bold text-slate-200">Pembayaran</span>
                        </div>
                        <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-300">Berhasil</span>
                    </div>
                    <p class="text-2xl font-black text-white">Rp 1.500.000</p>
                    <p class="text-xs text-slate-400 mt-1">Tagihan bulan ini lunas</p>
                </div>

                <!-- Mock Card 4: Dashboard Stats -->
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-md shadow-[0_20px_40px_rgba(0,0,0,0.4)]">
                    <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-4">Manajemen Properti</p>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-200">Kamar Terisi</span>
                            <span class="text-sm font-bold text-white">24 / 30</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-slate-700 overflow-hidden">
                            <div class="h-full w-[80%] rounded-full bg-gradient-to-r from-teal-400 to-indigo-400"></div>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-700 text-white text-[10px]"><Home class="w-3 h-3" /></div>
                        <p class="text-xs text-slate-300">+2 Pengajuan baru</p>
                    </div>
                </div>

            </div>
        </div>
        
        <!-- Grid overlay to give it a tech feeling -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik00MCAwaC0xTTAgNDB2LTFMMCAwaDQwdjQweiIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwgMjU1LCAyNTUsIDAuMDUpIiBzdHJva2Utd2lkdGg9IjEiLz4KPC9zdmc+')] opacity-40 pointer-events-none"></div>
    </div>
</template>

<style scoped>
@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-20px);
    }
}
</style>
