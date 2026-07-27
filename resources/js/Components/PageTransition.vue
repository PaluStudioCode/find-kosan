<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';

const isTransitioning = ref(false);
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

        // Cek URL tujuan
        const targetPath = visit.url.pathname;
        
        // Jangan tampilkan transisi jika berada di area dashboard (admin, superadmin, user, dll)
        if (targetPath.startsWith('/admin') || targetPath.startsWith('/superadmin') || targetPath.startsWith('/user') || targetPath.startsWith('/profile') || targetPath.startsWith('/reports')) {
            return;
        }
        
        isTransitioning.value = true;
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
    <transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 scale-105"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition-all duration-500 ease-in-out"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="isTransitioning" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-teal-600">
            <!-- Modern Animated Logo -->
            <div class="relative flex h-24 w-24 items-center justify-center rounded-2xl bg-white shadow-2xl animate-bounce">
                <Building2 class="h-12 w-12 text-teal-600 animate-pulse" />
            </div>
            
            <h2 class="mt-8 text-3xl font-extrabold text-white tracking-widest animate-pulse drop-shadow-md">
                CariKosan
            </h2>
            
            <!-- Loading Dots -->
            <div class="mt-5 flex gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-teal-100 animate-bounce" style="animation-delay: 0ms"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-teal-100 animate-bounce" style="animation-delay: 150ms"></span>
                <span class="h-2.5 w-2.5 rounded-full bg-teal-100 animate-bounce" style="animation-delay: 300ms"></span>
            </div>
        </div>
    </transition>
</template>
