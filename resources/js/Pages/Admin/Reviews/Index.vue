<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Deferred } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

import { Star, MessageSquare, ExternalLink, Filter, X } from 'lucide-vue-next';
import { format } from 'date-fns';
import { id as localeID } from 'date-fns/locale';

const props = defineProps({
    reviews: {
        type: Object,
        required: true,
    },
    kosList: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ kos_id: null, rating: null }),
    }
});

const filterKos = ref(props.filters.kos_id || '');
const filterRating = ref(props.filters.rating || '');

const applyFilters = () => {
    router.get(route('admin.reviews.index'), {
        kos_id: filterKos.value,
        rating: filterRating.value
    }, { preserveState: true, replace: true });
};

const resetFilters = () => {
    filterKos.value = '';
    filterRating.value = '';
    applyFilters();
};

watch([filterKos, filterRating], () => {
    applyFilters();
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return format(new Date(dateString), 'dd MMM yyyy', { locale: localeID });
};
</script>

<template>
    <Head title="Ulasan Penyewa" />

    <AppLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Ulasan Penyewa</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-1">Lihat bintang dan komentar dari penyewa yang pernah menetap di properti Anda.</p>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border dark:border-slate-800 p-4 mb-6">
            
            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4 mb-6 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg border border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2 mb-2 sm:mb-0 sm:w-auto text-sm font-medium text-slate-600 dark:text-slate-300">
                    <Filter class="w-4 h-4" /> Filter:
                </div>
                
                <select v-model="filterKos" class="border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 rounded-md text-sm focus:ring-teal-500 focus:border-teal-500 sm:w-64">
                    <option value="">Semua Properti Kos</option>
                    <option v-for="kos in kosList" :key="kos.id" :value="kos.id">{{ kos.name }}</option>
                </select>

                <select v-model="filterRating" class="border-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 rounded-md text-sm focus:ring-teal-500 focus:border-teal-500 sm:w-48">
                    <option value="">Semua Rating</option>
                    <option value="5">Bintang 5</option>
                    <option value="4">Bintang 4</option>
                    <option value="3">Bintang 3</option>
                    <option value="2">Bintang 2</option>
                    <option value="1">Bintang 1</option>
                </select>

                <button v-if="filterKos || filterRating" @click="resetFilters" class="text-sm text-slate-500 dark:text-slate-400 hover:text-red-500 dark:hover:text-red-400 flex items-center justify-center gap-1 sm:ml-auto transition-colors">
                    <X class="w-4 h-4" /> Reset
                </button>
            </div>

            <div class="mt-2">
                
                <Deferred data="reviews">
                    <template #fallback>
                        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <Card v-for="n in 6" :key="'skel-'+n" class="flex flex-col overflow-hidden animate-pulse">
                                <CardHeader class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 pb-4">
                                    <div class="flex items-start justify-between">
                                        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-24"></div>
                                        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-20"></div>
                                    </div>
                                    <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded w-3/4 mt-4"></div>
                                    <div class="flex items-center gap-2 mt-2">
                                        <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                                        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-1/3"></div>
                                    </div>
                                </CardHeader>
                                <CardContent class="flex-1 p-5 flex flex-col justify-between">
                                    <div class="space-y-2">
                                        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
                                        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-5/6"></div>
                                    </div>
                                    <div class="mt-6 flex justify-between gap-3 border-t pt-4 border-slate-100 dark:border-slate-800">
                                        <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-28"></div>
                                        <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-28"></div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </template>

                    <!-- If No Reviews -->
                <div v-if="!reviews.data.length" class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/50 py-20 px-4 text-center shadow-sm">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-teal-50 dark:bg-teal-950/30 mb-4">
                        <MessageSquare class="h-8 w-8 text-teal-500" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Belum Ada Ulasan</h3>
                    <p class="mt-2 max-w-sm text-sm text-slate-500 dark:text-slate-400">Belum ada penyewa yang memberikan ulasan untuk properti Anda.</p>
                </div>

                <!-- Reviews Grid -->
                <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="review in reviews.data" :key="review.id" class="flex flex-col overflow-hidden hover:shadow-md transition-shadow duration-300">
                        <CardHeader class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 pb-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center text-yellow-500">
                                    <Star v-for="n in 5" :key="n" :class="{'fill-current': n <= review.rating, 'text-slate-300 dark:text-slate-600': n > review.rating}" class="w-4 h-4" />
                                </div>
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ formatDate(review.created_at) }}</span>
                            </div>
                            <CardTitle class="mt-4 text-lg font-bold text-slate-900 dark:text-white line-clamp-1">
                                {{ review.boarding_house?.name || 'Properti Tidak Diketahui' }}
                            </CardTitle>
                            <CardDescription class="text-xs font-medium text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-2">
                                <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 font-bold overflow-hidden">
                                    <img v-if="review.user?.avatar_path" :src="review.user.avatar_path" class="w-full h-full object-cover" />
                                    <span v-else>{{ review.user?.name ? review.user.name.charAt(0) : '?' }}</span>
                                </div>
                                <span class="text-slate-700 dark:text-slate-300">{{ review.user?.name || 'Anonim' }}</span>
                            </CardDescription>
                        </CardHeader>
                        
                        <CardContent class="flex-1 p-5 flex flex-col justify-between">
                            <div>
                                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed bg-white dark:bg-slate-900/50 p-1 rounded-lg">
                                    "{{ review.comment || 'Penyewa tidak meninggalkan komentar.' }}"
                                </p>
                            </div>
                            
                            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400 dark:text-slate-500 border-t pt-4 border-slate-100 dark:border-slate-800">
                                <a :href="route('public.kos.show', review.boarding_house_id)" target="_blank" class="flex items-center gap-1 font-semibold text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 transition-colors bg-teal-50 dark:bg-teal-950/30 px-2 py-1.5 rounded border border-teal-100 dark:border-teal-900">
                                    <ExternalLink class="w-3.5 h-3.5" /> Halaman Publik
                                </a>
                                <Link :href="route('admin.kos.show', review.boarding_house_id)" class="font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                                    Kelola Properti &rarr;
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="reviews.links && reviews.links.length > 3" class="mt-8 flex justify-center">
                    <template v-for="(link, k) in reviews.links" :key="k">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm border dark:border-slate-800 rounded text-gray-400 dark:text-slate-500 bg-white dark:bg-slate-900" v-html="link.label" />
                        <Link v-else :href="link.url" preserve-scroll class="mr-1 mb-1 px-4 py-2 text-sm border rounded transition-colors" :class="link.active ? 'bg-teal-50 border-teal-500 text-teal-700 font-medium dark:bg-teal-900/50 dark:border-teal-500 dark:text-teal-300' : 'border-slate-200 dark:border-slate-800 text-slate-600 hover:bg-gray-50 dark:hover:bg-slate-800 bg-white dark:bg-slate-900 dark:text-slate-300'" v-html="link.label" />
                    </template>
                </div>
                </Deferred>

            </div>
        </div>
    </AppLayout>
</template>
