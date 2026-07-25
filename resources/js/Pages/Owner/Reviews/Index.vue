<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Star, MessageSquare, CheckCircle2 } from 'lucide-vue-next';
import { format } from 'date-fns';
import { id as localeID } from 'date-fns/locale';

const props = defineProps({
    reviews: {
        type: Object,
        required: true,
    }
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
            <h2 class="text-2xl font-bold text-gray-900">Ulasan Penyewa</h2>
            <p class="text-gray-500 mt-1">Lihat bintang dan komentar dari penyewa yang pernah menetap di properti Anda.</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
            <div class="mt-2">
                
                <!-- If No Reviews -->
                <div v-if="!reviews.data.length" class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white py-20 px-4 text-center shadow-sm">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-teal-50 mb-4">
                        <MessageSquare class="h-8 w-8 text-teal-500" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Belum Ada Ulasan</h3>
                    <p class="mt-2 max-w-sm text-sm text-slate-500">Belum ada penyewa yang memberikan ulasan untuk properti Anda.</p>
                </div>

                <!-- Reviews Grid -->
                <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="review in reviews.data" :key="review.id" class="flex flex-col overflow-hidden hover:shadow-md transition-shadow duration-300">
                        <CardHeader class="border-b border-slate-100 bg-slate-50/50 pb-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center text-yellow-500">
                                    <Star v-for="n in 5" :key="n" :class="{'fill-current': n <= review.rating, 'text-slate-300': n > review.rating}" class="w-4 h-4" />
                                </div>
                                <span class="text-xs font-semibold text-slate-500">{{ formatDate(review.created_at) }}</span>
                            </div>
                            <CardTitle class="mt-4 text-lg font-bold text-slate-900 line-clamp-1">
                                {{ review.boarding_house?.name || 'Properti Tidak Diketahui' }}
                            </CardTitle>
                            <CardDescription class="text-xs font-medium text-slate-500 flex items-center gap-2 mt-2">
                                <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold overflow-hidden">
                                    <img v-if="review.user?.avatar_path" :src="review.user.avatar_path" class="w-full h-full object-cover" />
                                    <span v-else>{{ review.user?.name ? review.user.name.charAt(0) : '?' }}</span>
                                </div>
                                <span class="text-slate-700">{{ review.user?.name || 'Anonim' }}</span>
                            </CardDescription>
                        </CardHeader>
                        
                        <CardContent class="flex-1 p-5 flex flex-col justify-between">
                            <div>
                                <p class="text-sm text-slate-600 leading-relaxed bg-white p-1 rounded-lg">
                                    "{{ review.comment || 'Penyewa tidak meninggalkan komentar.' }}"
                                </p>
                            </div>
                            
                            <div class="mt-6 flex items-center justify-end text-xs text-slate-400 border-t pt-4 border-slate-100">
                                <Link :href="route('owner.kos.show', review.boarding_house_id)" class="font-semibold text-teal-600 hover:text-teal-700 transition-colors">
                                    Lihat Properti &rarr;
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="reviews.links && reviews.links.length > 3" class="mt-8 flex justify-center">
                    <!-- Basic inertia pagination UI if required -->
                    <template v-for="(link, k) in reviews.links" :key="k">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm border rounded text-gray-400 bg-white" v-html="link.label" />
                        <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-gray-100 bg-white" :class="{ 'bg-slate-900 text-white hover:bg-slate-800 border-slate-900': link.active }" v-html="link.label" />
                    </template>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
