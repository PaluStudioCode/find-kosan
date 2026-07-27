<script setup>
import { Search } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: 'Data tidak ditemukan'
    },
    description: {
        type: String,
        default: 'Saat ini belum ada data yang dapat ditampilkan.'
    },
    icon: {
        type: [Object, Function],
        default: () => Search
    }
});
</script>

<template>
    <div class="text-center py-16 bg-white dark:bg-slate-900 rounded-lg border border-dashed dark:border-slate-800">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 dark:bg-slate-800/50 mb-4">
            <component :is="icon" class="w-8 h-8 text-gray-400 dark:text-slate-500" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ title }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400 max-w-sm mx-auto">{{ description }}</p>
        <div class="mt-6" v-if="$slots.default">
            <slot />
        </div>
    </div>
</template>
