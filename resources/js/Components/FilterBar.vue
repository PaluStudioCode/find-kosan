<script setup>
import { Button } from '@/components/ui/button';
import { Filter, X } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: 'Filter'
    }
});

const emit = defineEmits(['reset', 'apply']);
</script>

<template>
    <div class="bg-white p-4 rounded-lg border shadow-sm mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium flex items-center gap-2">
                <Filter class="w-4 h-4" /> {{ title }}
            </h3>
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="sm" @click="$emit('reset')" class="text-gray-500 hover:text-gray-900 h-8">
                    <X class="w-4 h-4 mr-1" /> Reset
                </Button>
                <Button size="sm" class="h-8" @click="$emit('apply')">Terapkan</Button>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <slot />
        </div>
    </div>
</template>
