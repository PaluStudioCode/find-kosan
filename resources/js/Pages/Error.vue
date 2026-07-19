<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { AlertCircle, FileQuestion, ShieldAlert, ServerCrash } from 'lucide-vue-next';

const props = defineProps({
  status: {
    type: Number,
    required: true,
  },
});

const details = computed(() => {
  return {
    503: {
      title: 'Layanan Tidak Tersedia',
      description: 'Sistem sedang dalam perbaikan. Silakan kembali lagi nanti.',
      icon: ServerCrash,
      color: 'text-orange-500'
    },
    500: {
      title: 'Terjadi Kesalahan Server',
      description: 'Mohon maaf, terjadi kesalahan pada sistem kami. Tim kami akan segera memperbaikinya.',
      icon: ServerCrash,
      color: 'text-red-500'
    },
    404: {
      title: 'Halaman Tidak Ditemukan',
      description: 'Halaman yang Anda tuju tidak ditemukan atau telah dipindahkan.',
      icon: FileQuestion,
      color: 'text-blue-500'
    },
    403: {
      title: 'Akses Ditolak',
      description: 'Anda tidak memiliki izin untuk mengakses halaman ini.',
      icon: ShieldAlert,
      color: 'text-red-500'
    },
  }[props.status] || {
    title: 'Terjadi Kesalahan',
    description: 'Mohon maaf, terjadi kesalahan yang tidak diketahui.',
    icon: AlertCircle,
    color: 'text-gray-500'
  };
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <Head :title="details.title" />
    
    <div class="max-w-md w-full text-center space-y-6 bg-white p-8 rounded-xl shadow-sm border border-gray-100">
      <div class="flex justify-center">
        <div class="p-4 bg-gray-50 rounded-full">
          <component :is="details.icon" class="w-16 h-16" :class="details.color" />
        </div>
      </div>
      
      <div class="space-y-2">
        <h1 class="text-3xl font-bold text-gray-900">{{ status }}</h1>
        <h2 class="text-xl font-semibold text-gray-800">{{ details.title }}</h2>
        <p class="text-gray-500">{{ details.description }}</p>
      </div>

      <div class="pt-6">
        <Link href="/">
          <Button class="w-full">
            Kembali ke Beranda
          </Button>
        </Link>
      </div>
    </div>
  </div>
</template>
