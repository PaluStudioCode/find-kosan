<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { ArrowLeft } from 'lucide-vue-next';
</script>

<template>
    <div>
        <Head title="Memuat..." />

        <!-- Hero Photo Gallery Skeleton -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div class="mb-4">
                <div class="inline-flex items-center text-sm text-slate-400">
                    <ArrowLeft class="w-4 h-4 mr-1" /> Kembali ke Pencarian
                </div>
            </div>

            <div class="relative rounded-2xl overflow-hidden">
                <div class="grid grid-cols-4 grid-rows-2 gap-1 h-[280px] sm:h-[380px]">
                    <div class="col-span-2 row-span-2 relative overflow-hidden">
                        <Skeleton class="w-full h-full rounded-none" />
                    </div>
                    <div class="relative overflow-hidden">
                        <Skeleton class="w-full h-full rounded-none" />
                    </div>
                    <div class="relative overflow-hidden">
                        <Skeleton class="w-full h-full rounded-none" />
                    </div>
                    <div class="relative overflow-hidden">
                        <Skeleton class="w-full h-full rounded-none" />
                    </div>
                    <div class="relative overflow-hidden">
                        <Skeleton class="w-full h-full rounded-none" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Skeleton -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Info Skeleton -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Title & Location -->
                    <div class="border-b border-slate-200 pb-6">
                        <Skeleton class="h-8 w-3/4 sm:w-1/2 mb-3" />
                        <Skeleton class="h-4 w-2/3 sm:w-1/3" />
                    </div>

                    <!-- Description -->
                    <div class="border-b border-slate-200 pb-6">
                        <h2 class="text-lg font-bold text-slate-900 mb-3">Tentang Kos Ini</h2>
                        <div class="space-y-2">
                            <Skeleton class="h-4 w-full" />
                            <Skeleton class="h-4 w-full" />
                            <Skeleton class="h-4 w-5/6" />
                            <Skeleton class="h-4 w-4/6" />
                        </div>
                    </div>

                    <!-- Facilities -->
                    <div class="border-b border-slate-200 pb-6">
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Fasilitas Umum</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div v-for="i in 6" :key="i" class="flex items-center gap-3">
                                <Skeleton class="w-5 h-5 rounded-full shrink-0" />
                                <Skeleton class="h-4 w-24" />
                            </div>
                        </div>
                    </div>

                    <!-- Rules -->
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 mb-4">Peraturan Kos</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div v-for="i in 5" :key="i" class="flex items-start gap-3">
                                <Skeleton class="w-5 h-5 rounded-full shrink-0" />
                                <Skeleton class="h-4 w-20" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Sticky Summary Card Skeleton -->
                <div>
                    <div class="sticky top-24 bg-white border border-slate-200 rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.08)] space-y-6">
                        <div>
                            <Skeleton class="h-4 w-24 mb-2" />
                            <Skeleton class="h-8 w-40" />
                        </div>
                        
                        <div class="flex items-center justify-between border-y border-slate-100 py-4">
                            <Skeleton class="h-4 w-28" />
                            <Skeleton class="h-6 w-24 rounded-full" />
                        </div>
                        
                        <div class="flex items-center justify-between pb-2">
                            <Skeleton class="h-5 w-32" />
                        </div>
                        
                        <div class="space-y-3 pt-2">
                            <Skeleton class="h-12 w-full rounded-full" />
                        </div>
                        
                        <div class="pt-2 flex justify-center">
                            <Skeleton class="h-4 w-32" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rooms Section Skeleton -->
            <div class="mt-12 border-t border-slate-200 pt-8">
                <h2 class="text-2xl font-extrabold text-slate-900 mb-6">Pilihan Kamar</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div v-for="i in 3" :key="i" class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="w-full">
                                <Skeleton class="h-6 w-3/4 mb-2" />
                                <Skeleton class="h-4 w-16" />
                            </div>
                            <Skeleton class="h-6 w-20 rounded-lg shrink-0" />
                        </div>

                        <div class="mb-4">
                            <Skeleton class="h-8 w-32" />
                        </div>

                        <div class="mb-4 space-y-2">
                            <Skeleton class="h-4 w-full" />
                            <Skeleton class="h-4 w-4/5" />
                        </div>

                        <div class="mb-6 flex flex-wrap gap-2">
                            <Skeleton v-for="j in 3" :key="j" class="h-6 w-16 rounded-md" />
                        </div>

                        <div class="mt-auto">
                            <Skeleton class="h-11 w-full rounded-full" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rating & Reviews Section Skeleton -->
            <section class="mt-12 border-t border-slate-200 pt-10">
                <div class="mb-6">
                    <Skeleton class="h-7 w-48 mb-2" />
                    <Skeleton class="h-4 w-64" />
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-1">
                        <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div>
                                <Skeleton class="h-5 w-32 mb-2" />
                                <Skeleton class="h-3 w-48" />
                            </div>

                            <div class="space-y-2">
                                <Skeleton class="h-4 w-12" />
                                <div class="flex gap-1">
                                    <Skeleton v-for="s in 5" :key="s" class="h-7 w-7 rounded-sm" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Skeleton class="h-4 w-20" />
                                <Skeleton class="h-24 w-full rounded-md" />
                            </div>

                            <Skeleton class="h-10 w-full rounded-md" />
                        </div>
                    </div>

                    <div class="space-y-3 lg:col-span-2">
                        <div v-for="i in 3" :key="i" class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <Skeleton class="h-4 w-24 mb-1" />
                                    <Skeleton class="h-3 w-16" />
                                </div>
                                <div class="flex gap-1">
                                    <Skeleton v-for="s in 5" :key="s" class="h-4 w-4 rounded-sm" />
                                </div>
                            </div>
                            <div class="mt-3 space-y-2">
                                <Skeleton class="h-4 w-full" />
                                <Skeleton class="h-4 w-5/6" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
