<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import { watch } from 'vue';

const page = usePage();

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
    if (flash?.status) toast.info(flash.status);
}, { deep: true, immediate: true });
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <header class="bg-white shadow-sm sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <div class="flex items-center">
            <Link href="/" class="text-xl font-bold text-primary">Kos Online</Link>
          </div>
          <div class="flex items-center gap-4">
            <template v-if="$page.props.auth.user">
              <Link :href="route('dashboard')">
                <Button variant="ghost">Dashboard</Button>
              </Link>
            </template>
            <template v-else>
              <Link :href="route('login')">
                <Button variant="outline">Login / Daftar</Button>
              </Link>
            </template>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow">
      <slot />
    </main>
    
    <footer class="bg-white border-t py-8 text-center text-sm text-gray-500">
      &copy; {{ new Date().getFullYear() }} Kos Online. All rights reserved.
    </footer>
    
    <Toaster position="top-right" richColors expand />
  </div>
</template>
