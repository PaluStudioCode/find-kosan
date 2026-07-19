<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
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
    <div class="flex min-h-screen flex-col items-center justify-center bg-muted/40 p-4">
        <div class="mb-8 flex flex-col items-center gap-2">
            <Link href="/" class="flex items-center gap-2 text-xl font-bold hover:text-primary transition-colors">
                <Building2 class="h-6 w-6 text-primary" />
                <span>Website Pencarian dan Manajemen Kos</span>
            </Link>
        </div>

        <div class="w-full max-w-md">
            <slot />
        </div>
        
        <Toaster position="top-right" richColors expand />
    </div>
</template>
