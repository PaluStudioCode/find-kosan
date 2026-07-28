<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    content: String,
});
</script>

<template>
    <PublicLayout>
        <Head :title="title" />

        <div class="py-12 bg-gray-50 dark:bg-slate-950 min-h-[70vh]">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row gap-8">
                    <!-- Sidebar Navigation -->
                    <div class="w-full md:w-64 shrink-0">
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden sticky top-24">
                            <div class="p-4 bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Informasi Umum</h3>
                            </div>
                            <nav class="flex flex-col p-2 gap-1">
                                <Link :href="route('page.show', 'tentang-kami')" 
                                      preserve-state preserve-scroll
                                      class="px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                                      :class="route().current('page.show', 'tentang-kami') ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400' : 'text-gray-600 hover:bg-gray-50 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200'">
                                    Tentang Kami
                                </Link>
                                <Link :href="route('page.show', 'syarat-ketentuan')" 
                                      preserve-state preserve-scroll
                                      class="px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                                      :class="route().current('page.show', 'syarat-ketentuan') ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400' : 'text-gray-600 hover:bg-gray-50 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200'">
                                    Syarat & Ketentuan
                                </Link>
                                <Link :href="route('page.show', 'kebijakan-privasi')" 
                                      preserve-state preserve-scroll
                                      class="px-4 py-2.5 rounded-lg text-sm font-medium transition-colors"
                                      :class="route().current('page.show', 'kebijakan-privasi') ? 'bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400' : 'text-gray-600 hover:bg-gray-50 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200'">
                                    Kebijakan Privasi
                                </Link>
                            </nav>
                        </div>
                    </div>

                    <!-- Content Area -->
                    <div class="flex-1 bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 p-8 md:p-10">
                        <div class="mb-8 border-b dark:border-slate-800 pb-6">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ title }}</h1>
                        </div>

                        <div class="prose prose-teal dark:prose-invert max-w-none text-gray-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed">
                            {{ content }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
