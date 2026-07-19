<script setup>
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

defineProps({
    columns: {
        type: Array,
        required: true // [{ key: 'id', label: 'ID', class: '' }]
    },
    data: {
        type: Object,
        required: true // Laravel paginator object: { data: [], current_page, last_page, total, from, to, links }
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['page-change']);
</script>

<template>
    <div class="bg-white rounded-md border shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead v-for="col in columns" :key="col.key" :class="col.class">
                            {{ col.label }}
                        </TableHead>
                        <TableHead v-if="$slots.actions" class="text-right">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="loading">
                        <TableRow v-for="i in 5" :key="`skeleton-${i}`">
                            <TableCell v-for="col in columns" :key="`skeleton-col-${col.key}`">
                                <div class="h-4 bg-gray-200 rounded animate-pulse w-full"></div>
                            </TableCell>
                            <TableCell v-if="$slots.actions">
                                <div class="h-4 bg-gray-200 rounded animate-pulse w-8 ml-auto"></div>
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else-if="!data?.data || data.data.length === 0">
                        <TableRow>
                            <TableCell :colspan="columns.length + ($slots.actions ? 1 : 0)" class="h-24 text-center">
                                Tidak ada data.
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow v-for="(row, index) in data.data" :key="row.id || index">
                            <TableCell v-for="col in columns" :key="col.key" :class="col.class">
                                <slot :name="`cell-${col.key}`" :row="row">
                                    {{ row[col.key] }}
                                </slot>
                            </TableCell>
                            <TableCell v-if="$slots.actions" class="text-right">
                                <slot name="actions" :row="row"></slot>
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>
        
        <!-- Pagination -->
        <div v-if="data?.total > 0 && !loading" class="flex flex-col sm:flex-row items-center justify-between px-4 py-3 border-t gap-4">
            <div class="text-sm text-gray-500 text-center sm:text-left">
                Menampilkan <span class="font-medium">{{ data.from }}</span> - <span class="font-medium">{{ data.to }}</span> dari <span class="font-medium">{{ data.total }}</span> data
            </div>
            <div class="flex gap-1" v-if="data.last_page > 1">
                <Button 
                    variant="outline" 
                    size="sm" 
                    :disabled="data.current_page === 1"
                    @click="$emit('page-change', data.current_page - 1)"
                >
                    <ChevronLeft class="w-4 h-4" />
                </Button>
                
                <div class="flex items-center gap-1 mx-2">
                    <span class="text-sm">Hal {{ data.current_page }} dari {{ data.last_page }}</span>
                </div>
                
                <Button 
                    variant="outline" 
                    size="sm" 
                    :disabled="data.current_page === data.last_page"
                    @click="$emit('page-change', data.current_page + 1)"
                >
                    <ChevronRight class="w-4 h-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
