<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { MapPin, Search } from 'lucide-vue-next';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    boardingHouses: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const handleSearch = () => {
    router.get(route('public.kos.index'), { search: search.value }, { preserveState: true });
};
</script>

<template>
    <PublicLayout>
        <Head title="Cari Kos" />

        <div class="bg-primary/5 py-8 border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4">
                <h1 class="text-3xl font-bold text-gray-900">Temukan Kos Impianmu</h1>
                
                <form @submit.prevent="handleSearch" class="max-w-xl mx-auto mt-4 flex gap-2">
                    <Input v-model="search" placeholder="Cari nama kos, kota, atau kecamatan..." class="h-12 bg-white" />
                    <Button type="submit" size="lg" class="h-12 px-8">
                        <Search class="w-5 h-5 mr-2" /> Cari
                    </Button>
                </form>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Hasil Pencarian</h2>
            </div>

            <div v-if="boardingHouses.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card v-for="kos in boardingHouses.data" :key="kos.id" class="overflow-hidden hover:shadow-md transition-shadow">
                    <img v-if="kos.photos && kos.photos.length > 0" :src="kos.photos[0].file_path" class="w-full h-48 object-cover" />
                    <div v-else class="w-full h-48 bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-400">Tidak ada foto</span>
                    </div>
                    
                    <CardHeader class="p-4 pb-0">
                        <CardTitle class="text-lg line-clamp-1">
                            <Link :href="route('public.kos.show', kos.id)" class="hover:text-primary">
                                {{ kos.name }}
                            </Link>
                        </CardTitle>
                        <p class="text-sm text-gray-500 flex items-center mt-1">
                            <MapPin class="w-3 h-3 mr-1" /> {{ kos.city }}, {{ kos.district }}
                        </p>
                    </CardHeader>
                    <CardContent class="p-4">
                        <div class="flex flex-wrap gap-1 mt-2">
                            <span v-for="fac in kos.facilities.slice(0, 3)" :key="fac.id" class="px-2 py-1 bg-gray-100 text-xs rounded-full text-gray-600">
                                {{ fac.name }}
                            </span>
                            <span v-if="kos.facilities.length > 3" class="px-2 py-1 bg-gray-100 text-xs rounded-full text-gray-600">
                                +{{ kos.facilities.length - 3 }} lainnya
                            </span>
                        </div>
                    </CardContent>
                    <CardFooter class="p-4 pt-0 flex justify-between items-center border-t mt-4 pt-4">
                        <Link :href="route('public.kos.show', kos.id)" class="w-full">
                            <Button class="w-full" variant="outline">Lihat Detail</Button>
                        </Link>
                    </CardFooter>
                </Card>
            </div>
            
            <EmptyState v-else title="Kos Tidak Ditemukan" description="Coba ubah kata kunci pencarian Anda." />
            
            <!-- Pagination -->
            <div v-if="boardingHouses.links && boardingHouses.links.length > 3" class="mt-8 flex justify-center gap-1">
                <template v-for="(link, k) in boardingHouses.links" :key="k">
                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm border rounded text-gray-400 bg-white" v-html="link.label" />
                    <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-2 text-sm border rounded hover:bg-gray-100 focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-primary text-white hover:bg-primary': link.active }" v-html="link.label" />
                </template>
            </div>
        </div>
    </PublicLayout>
</template>
