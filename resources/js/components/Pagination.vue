<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    }
});
</script>

<template>
    <div v-if="links && links.length > 3" class="mt-6 flex flex-wrap justify-center gap-1 border-t dark:border-slate-800 pt-4">
        <template v-for="(link, k) in links" :key="k">
            <div 
                v-if="link.url === null" 
                class="mr-1 mb-1 px-4 py-2 text-sm border dark:border-slate-700 rounded text-gray-400 dark:text-slate-500 bg-white dark:bg-slate-800" 
                v-html="link.label" 
            />
            <Link 
                v-else 
                :href="link.url" 
                preserve-scroll
                class="mr-1 mb-1 px-4 py-2 text-sm border dark:border-slate-700 rounded transition-colors" 
                :class="link.active 
                    ? 'bg-gray-900 text-white hover:bg-gray-800 border-gray-900 dark:bg-white dark:text-slate-900 dark:border-white dark:hover:bg-gray-200 pointer-events-none' 
                    : 'hover:bg-gray-100 dark:hover:bg-slate-700 bg-white dark:bg-slate-800 dark:text-slate-300'" 
                v-html="link.label" 
            />
        </template>
    </div>
    <div v-else-if="!links || links.length === 0" class="mt-6 flex flex-wrap justify-center gap-1 border-t dark:border-slate-800 pt-4 pointer-events-none opacity-60">
        <div class="mr-1 mb-1 px-4 py-2 text-sm border dark:border-slate-700 rounded text-gray-400 dark:text-slate-500 bg-white dark:bg-slate-800">&laquo; Sebelumnya</div>
        <div class="mr-1 mb-1 px-4 py-2 text-sm border dark:border-slate-700 rounded bg-gray-900 text-white dark:bg-white dark:text-slate-900">1</div>
        <div class="mr-1 mb-1 px-4 py-2 text-sm border dark:border-slate-700 rounded text-gray-400 dark:text-slate-500 bg-white dark:bg-slate-800">Selanjutnya &raquo;</div>
    </div>
</template>
