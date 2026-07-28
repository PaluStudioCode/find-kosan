<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Home, MapPin, Search } from 'lucide-vue-next';

const isTransitioning = ref(false);
const transitionType = ref('default');
let timeout = null;

onMounted(() => {
    router.on('start', (event) => {
        const visit = event.detail.visit;
        
        // Jangan tampilkan transisi jika ini adalah form submission (POST/PUT/DELETE)
        if (visit.method !== 'get') {
            return;
        }
        
        // Jangan tampilkan transisi jika preserveState aktif (biasanya untuk filter/search/pagination)
        if (visit.preserveState) {
            return;
        }

        // Cek URL saat ini
        const currentPath = window.location.pathname;

        // Cek URL tujuan
        const targetPath = visit.url.pathname;
        
        // Halaman autentikasi
        const isCurrentAuthPage = currentPath === '/login' || 
                                  currentPath === '/register' || 
                                  currentPath.startsWith('/forgot-password') || 
                                  currentPath.startsWith('/reset-password');

        const isTargetAuthPage = targetPath === '/login' || 
                           targetPath === '/register' || 
                           targetPath.startsWith('/forgot-password') || 
                           targetPath.startsWith('/reset-password');
                           
        if (isCurrentAuthPage && isTargetAuthPage) {
            return;
        }
                           
        // Halaman peta kos
        const isMapPage = targetPath === '/kos' || targetPath.startsWith('/kos/');

        if (isMapPage) {
            transitionType.value = 'clouds';
            isTransitioning.value = true;
        } else if (isTargetAuthPage) {
            transitionType.value = 'default';
            isTransitioning.value = true;
        } else {
            // Selain halaman peta dan autentikasi, jangan ada transisi
            isTransitioning.value = false;
        }
    });

    router.on('finish', () => {
        if (!isTransitioning.value) return;
        
        // Keep the splash screen up for a fraction of a second after load to ensure DOM is ready and it looks smooth
        timeout = setTimeout(() => {
            isTransitioning.value = false;
        }, 400); 
    });
});

onUnmounted(() => {
    if (timeout) clearTimeout(timeout);
});
</script>

<template>
    <!-- Friendly & Cozy Default Transition -->
    <transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-400 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="isTransitioning && transitionType === 'default'" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-teal-50/95 backdrop-blur-sm">
            
            <!-- Character Animation: Cute Hopping Mascot -->
            <div class="relative flex flex-col items-center justify-center h-32 w-32">
                <!-- Mascot Body -->
                <div class="relative w-20 h-20 animate-[hop_0.8s_ease-in-out_infinite] z-10">
                    <!-- Main Body (Teal Blob) -->
                    <div class="absolute inset-0 bg-teal-400 rounded-full shadow-inner overflow-hidden">
                        <!-- Belly reflection -->
                        <div class="absolute -bottom-2 -right-2 w-16 h-16 bg-teal-300 rounded-full opacity-50"></div>
                    </div>
                    
                    <!-- Left Eye -->
                    <div class="absolute top-7 left-4 w-2.5 h-3.5 bg-slate-800 rounded-full animate-[blink_3s_infinite]"></div>
                    <!-- Right Eye -->
                    <div class="absolute top-7 right-4 w-2.5 h-3.5 bg-slate-800 rounded-full animate-[blink_3s_infinite]"></div>
                    
                    <!-- Cheeks (Blush) -->
                    <div class="absolute top-9 left-2 w-3 h-2 bg-pink-300 rounded-full opacity-70"></div>
                    <div class="absolute top-9 right-2 w-3 h-2 bg-pink-300 rounded-full opacity-70"></div>
                    
                    <!-- Mouth / Beak -->
                    <div class="absolute top-9 left-1/2 -translate-x-1/2 w-3 h-2.5 bg-yellow-400 rounded-full"></div>
                    
                    <!-- Left Wing -->
                    <div class="absolute top-10 -left-3 w-5 h-7 bg-teal-500 rounded-full origin-top-right animate-[flap-left_0.8s_ease-in-out_infinite]"></div>
                    <!-- Right Wing -->
                    <div class="absolute top-10 -right-3 w-5 h-7 bg-teal-500 rounded-full origin-top-left animate-[flap-right_0.8s_ease-in-out_infinite]"></div>
                </div>
                
                <!-- Floor Shadow (Shrinks when jumping) -->
                <div class="absolute bottom-4 w-12 h-3 bg-teal-900 rounded-full blur-[2px] animate-[shadow-shrink_0.8s_ease-in-out_infinite]"></div>
            </div>
            
            <!-- Friendly Text -->
            <div class="mt-6 flex flex-col items-center">
                <h2 class="text-xl font-bold text-teal-800 tracking-wide">
                    Tunggu sebentar ya...
                </h2>
                
                <!-- Playful Chat-style Bouncing Dots -->
                <div class="mt-4 flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-teal-400 animate-bounce" style="animation-delay: 0ms"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-teal-400 animate-bounce" style="animation-delay: 150ms"></span>
                    <span class="h-2.5 w-2.5 rounded-full bg-teal-400 animate-bounce" style="animation-delay: 300ms"></span>
                </div>
            </div>
            
        </div>
    </transition>

    <!-- Clash of Clans Style Clouds Transition -->
    <transition
        enter-active-class="transition-all duration-700 ease-out"
        enter-from-class="coc-clouds-open"
        enter-to-class="coc-clouds-closed"
        leave-active-class="transition-all duration-[800ms] ease-in"
        leave-from-class="coc-clouds-closed"
        leave-to-class="coc-clouds-open"
    >
        <div v-if="isTransitioning && transitionType === 'clouds'" class="fixed inset-0 z-[9999] pointer-events-none flex items-center justify-center overflow-hidden">
            
            <!-- White Background overlay that fades in slightly delayed to ensure full coverage -->
            <div class="absolute inset-0 bg-white transition-opacity duration-300" 
                 :class="isTransitioning ? 'opacity-100 delay-300' : 'opacity-0'"></div>
                 


            <!-- LAYER 1: Background Fog (Heavily Blurred, Soft Gray) -->
            <svg viewBox="0 0 800 800" class="cloud-left absolute top-[-10%] left-[-20%] w-[160vw] h-[140vh] text-slate-200 fill-current blur-3xl opacity-80 transition-transform duration-1000 ease-out z-10" style="transition-delay: 0ms;">
                <circle cx="300" cy="400" r="250" />
                <circle cx="500" cy="300" r="300" />
                <circle cx="700" cy="500" r="250" />
                <rect x="200" y="300" width="600" height="500" />
            </svg>
            <svg viewBox="0 0 800 800" class="cloud-right absolute top-[-10%] right-[-20%] w-[160vw] h-[140vh] text-slate-200 fill-current blur-3xl opacity-80 transition-transform duration-1000 ease-out z-10" style="transform: scaleX(-1); transition-delay: 0ms;">
                <circle cx="300" cy="400" r="250" />
                <circle cx="500" cy="300" r="300" />
                <circle cx="700" cy="500" r="250" />
                <rect x="200" y="300" width="600" height="500" />
            </svg>

            <!-- LAYER 2: Midground Mist (Medium Blur, White) -->
            <svg viewBox="0 0 800 800" class="cloud-left absolute top-[0%] left-[-15%] w-[140vw] h-[120vh] text-slate-100 fill-current blur-xl opacity-90 transition-transform duration-700 ease-out z-20" style="transition-delay: 100ms;">
                <circle cx="250" cy="400" r="180" />
                <circle cx="450" cy="250" r="220" />
                <circle cx="650" cy="350" r="190" />
                <circle cx="750" cy="550" r="150" />
                <rect x="200" y="300" width="550" height="400" />
            </svg>
            <svg viewBox="0 0 800 800" class="cloud-right absolute top-[0%] right-[-15%] w-[140vw] h-[120vh] text-slate-100 fill-current blur-xl opacity-90 transition-transform duration-700 ease-out z-20" style="transform: scaleX(-1); transition-delay: 100ms;">
                <circle cx="250" cy="450" r="160" />
                <circle cx="450" cy="300" r="240" />
                <circle cx="650" cy="400" r="200" />
                <circle cx="750" cy="600" r="170" />
                <rect x="200" y="350" width="550" height="400" />
            </svg>

            <!-- LAYER 3: Foreground Dense Clouds (Slight Blur for softness, Pure White) -->
            <svg viewBox="0 0 1200 600" class="cloud-top absolute top-[-20%] left-[-10%] w-[120vw] h-[150vh] text-white fill-current blur-md drop-shadow-[0_25px_35px_rgba(0,0,0,0.15)] transition-transform duration-[800ms] ease-out z-30" style="transition-delay: 150ms;">
                <circle cx="200" cy="200" r="180" />
                <circle cx="450" cy="300" r="250" />
                <circle cx="750" cy="250" r="220" />
                <circle cx="1000" cy="150" r="160" />
                <rect x="100" y="0" width="1000" height="250" />
            </svg>
            <svg viewBox="0 0 1200 600" class="cloud-bottom absolute bottom-[-20%] left-[-10%] w-[120vw] h-[150vh] text-white fill-current blur-md drop-shadow-[0_-25px_35px_rgba(0,0,0,0.15)] transition-transform duration-[800ms] ease-out z-30" style="transition-delay: 200ms;">
                <circle cx="250" cy="400" r="190" />
                <circle cx="550" cy="250" r="260" />
                <circle cx="850" cy="350" r="230" />
                <circle cx="1050" cy="450" r="170" />
                <rect x="150" y="350" width="950" height="250" />
            </svg>

            <!-- Foreground Elements (Text inside clouds) -->
            <div class="cloud-content relative z-20 flex flex-col items-center transition-all duration-300">
                <div class="relative flex items-center justify-center mb-6">
                    <Search class="h-16 w-16 text-teal-600 drop-shadow-xl animate-bounce" stroke-width="2.5" />
                </div>
                
                <h2 class="text-4xl font-extrabold text-teal-800 tracking-tight drop-shadow-sm">
                    Mencari Lokasi...
                </h2>
                <div class="mt-4 flex items-center gap-3">
                    <div class="w-2 h-2 bg-amber-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-2 h-2 bg-teal-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-2 h-2 bg-amber-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.coc-clouds-open .cloud-left { transform: translateX(-110%); }
.coc-clouds-open .cloud-right { transform: translateX(110%) scaleX(-1); }
.coc-clouds-open .cloud-top { transform: translateY(-110%); }
.coc-clouds-open .cloud-bottom { transform: translateY(110%); }
.coc-clouds-open .cloud-content { opacity: 0; transform: scale(0.5); }

.coc-clouds-closed .cloud-left { transform: translateX(0); }
.coc-clouds-closed .cloud-right { transform: translateX(0) scaleX(-1); }
.coc-clouds-closed .cloud-top { transform: translateY(0); }
.coc-clouds-closed .cloud-bottom { transform: translateY(0); }
.coc-clouds-closed .cloud-content { opacity: 1; transform: scale(1); transition-delay: 500ms; }
@keyframes float-cloud-1 {
    0% { transform: translateX(-5%); }
    50% { transform: translateX(5%); }
    100% { transform: translateX(-5%); }
}
@keyframes float-cloud-2 {
    0% { transform: translateX(5%); }
    50% { transform: translateX(-5%); }
    100% { transform: translateX(5%); }
}
@keyframes float-pin {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}
@keyframes hop {
    0%, 100% { transform: translateY(0) scaleY(0.95) scaleX(1.05); }
    50% { transform: translateY(-25px) scaleY(1.05) scaleX(0.95); }
}
@keyframes blink {
    0%, 94%, 98%, 100% { transform: scaleY(1); }
    96% { transform: scaleY(0.1); }
}
@keyframes flap-left {
    0%, 100% { transform: rotate(20deg); }
    50% { transform: rotate(-40deg); }
}
@keyframes flap-right {
    0%, 100% { transform: rotate(-20deg); }
    50% { transform: rotate(40deg); }
}
@keyframes shadow-shrink {
    0%, 100% { transform: scaleX(1); opacity: 0.25; }
    50% { transform: scaleX(0.5); opacity: 0.1; }
}
</style>
