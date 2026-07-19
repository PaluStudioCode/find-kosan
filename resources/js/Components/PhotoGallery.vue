<script setup>
import { ref } from 'vue';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, X } from 'lucide-vue-next';

const props = defineProps({
    photos: {
        type: Array,
        required: true
    }
});

const currentPhotoIndex = ref(0);
const isGalleryOpen = ref(false);

const openGallery = (index = 0) => {
    currentPhotoIndex.value = index;
    isGalleryOpen.value = true;
};

const nextPhoto = () => {
    if (currentPhotoIndex.value < props.photos.length - 1) {
        currentPhotoIndex.value++;
    } else {
        currentPhotoIndex.value = 0;
    }
};

const prevPhoto = () => {
    if (currentPhotoIndex.value > 0) {
        currentPhotoIndex.value--;
    } else {
        currentPhotoIndex.value = props.photos.length - 1;
    }
};
</script>

<template>
    <div v-if="photos.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-2 rounded-lg overflow-hidden">
        <!-- Main Photo -->
        <div class="md:col-span-2 md:row-span-2 cursor-pointer group relative" @click="openGallery(0)">
            <img :src="photos[0].url" :alt="photos[0].caption || 'Foto Kos'" class="w-full h-full object-cover aspect-video md:aspect-square" />
            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>
        
        <!-- Other Photos -->
        <template v-for="(photo, index) in photos.slice(1, 5)" :key="photo.id">
            <div class="hidden md:block cursor-pointer group relative" @click="openGallery(index + 1)">
                <img :src="photo.url" :alt="photo.caption || 'Foto Kos'" class="w-full h-full object-cover aspect-square" />
                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                
                <div v-if="index === 3 && photos.length > 5" class="absolute inset-0 bg-black/50 flex items-center justify-center">
                    <span class="text-white font-medium text-lg">+{{ photos.length - 5 }} Foto</span>
                </div>
            </div>
        </template>

        <!-- Fullscreen Gallery Dialog -->
        <Dialog v-model:open="isGalleryOpen">
            <DialogContent class="max-w-4xl bg-black/95 p-0 border-none shadow-none flex flex-col justify-center h-screen sm:h-auto sm:max-h-[90vh] sm:rounded-lg overflow-hidden [&>button]:hidden">
                <div class="absolute top-4 right-4 z-50">
                    <Button variant="ghost" size="icon" class="text-white hover:bg-white/20" @click="isGalleryOpen = false">
                        <X class="w-6 h-6" />
                    </Button>
                </div>
                
                <div class="relative flex items-center justify-center flex-1 min-h-0">
                    <Button v-if="photos.length > 1" variant="ghost" size="icon" class="absolute left-4 text-white hover:bg-white/20 z-50" @click="prevPhoto">
                        <ChevronLeft class="w-8 h-8" />
                    </Button>
                    
                    <img :src="photos[currentPhotoIndex].url" :alt="photos[currentPhotoIndex].caption" class="max-w-full max-h-[80vh] object-contain" />
                    
                    <Button v-if="photos.length > 1" variant="ghost" size="icon" class="absolute right-4 text-white hover:bg-white/20 z-50" @click="nextPhoto">
                        <ChevronRight class="w-8 h-8" />
                    </Button>
                </div>
                
                <div v-if="photos[currentPhotoIndex].caption" class="text-white text-center py-4 bg-black/50 absolute bottom-0 w-full">
                    {{ photos[currentPhotoIndex].caption }}
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
