<script setup>
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true
    }
});

const statusConfig = computed(() => {
    const config = {
        'draft': { label: 'Draft', variant: 'secondary' },
        'menunggu_verifikasi': { label: 'Menunggu Verifikasi', variant: 'warning' },
        'dipublikasikan': { label: 'Dipublikasikan', variant: 'success' },
        'ditolak': { label: 'Ditolak', variant: 'destructive' },
        'nonaktif': { label: 'Nonaktif', variant: 'outline' },
        'aktif': { label: 'Aktif', variant: 'success' },
        'menunggu_aktivasi': { label: 'Menunggu Aktivasi', variant: 'warning' },
        'lunas': { label: 'Lunas', variant: 'success' },
        'belum_dibayar': { label: 'Belum Dibayar', variant: 'destructive' },
        'menunggu_konfirmasi': { label: 'Menunggu Konfirmasi', variant: 'warning' },
    };
    
    return config[props.status] || { label: props.status, variant: 'outline' };
});
</script>

<template>
    <Badge :variant="statusConfig.variant">{{ statusConfig.label }}</Badge>
</template>
