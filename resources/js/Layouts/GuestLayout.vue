<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import AuthFeatureShowcase from '@/components/AuthFeatureShowcase.vue';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import { watch } from 'vue';

const page = usePage();

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
    if (flash?.status) toast.info(flash.status);
}, { deep: true, immediate: true });
</script>

<template>
    <div class="flex min-h-screen w-full bg-white">
        <!-- Left Side: Form (Mobile + Desktop) -->
        <div class="flex w-full flex-col justify-start pt-16 lg:pt-32 px-4 sm:px-6 lg:flex-none lg:w-1/2 xl:w-5/12 bg-slate-50 lg:bg-white relative z-10 shadow-[0_0_40px_rgba(0,0,0,0.05)] lg:shadow-none">
            <div class="mx-auto w-full max-w-md py-12">
                <!-- Logo -->
                <div class="mb-10 flex justify-center lg:justify-start">
                    <Link href="/" class="flex items-center gap-2.5 text-2xl font-extrabold text-slate-900 group">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500 text-white shadow-md transition-transform group-hover:scale-105">
                            <Building2 class="h-6 w-6" />
                        </div>
                        <span class="bg-gradient-to-r from-teal-600 to-slate-800 bg-clip-text text-transparent">CariKosan</span>
                    </Link>
                </div>
                
                <!-- Content Slot (Cards will fit here perfectly) -->
                <div class="w-full">
                    <slot />
                </div>
            </div>
        </div>
        
        <!-- Right Side: Showcase (Desktop Only) -->
        <div class="relative hidden w-0 flex-1 lg:block bg-slate-900 overflow-hidden">
            <AuthFeatureShowcase class="absolute inset-0 h-full w-full opacity-100 transition-opacity duration-1000" />
            
            <!-- Soft Gradient Overlay (Dark) -->
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent pointer-events-none"></div>
            
            <!-- Branding Text (Light Text) -->
            <div class="absolute bottom-16 left-16 right-16 text-white max-w-2xl pointer-events-none z-10">
                <blockquote class="space-y-6">
                    <p class="text-3xl xl:text-4xl font-extrabold leading-tight text-white drop-shadow-lg">
                        "Mencari kos dan manajemen penyewaan kini menjadi lebih cepat, aman, dan tanpa repot."
                    </p>
                    <footer class="flex items-center gap-3">
                        <div class="h-px w-8 bg-teal-400"></div>
                        <span class="text-teal-300 font-bold tracking-wide uppercase text-sm drop-shadow-md">Temukan Kenyamanan Anda</span>
                    </footer>
                </blockquote>
            </div>
        </div>
        
        <Toaster position="top-right" richColors expand />
    </div>
</template>
