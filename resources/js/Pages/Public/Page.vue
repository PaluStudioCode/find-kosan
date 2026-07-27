<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    title: String,
    content: String,
});
</script>

<template>
    <PublicLayout>
        <Head :title="title" />

        <div class="py-16 bg-white dark:bg-slate-900 min-h-[60vh]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ title }}</h1>
                    <div class="w-16 h-1 bg-teal-500 mx-auto mt-6 rounded-full"></div>
                </div>

                <div class="prose prose-teal dark:prose-invert max-w-none text-gray-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">
                    {{ content }}
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
