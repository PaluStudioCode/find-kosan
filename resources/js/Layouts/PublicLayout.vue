<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import { watch } from 'vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { User, LogOut, FileText, LayoutDashboard, Home } from 'lucide-vue-next';

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
          <div class="flex items-center gap-6">
            <Link href="/" class="text-xl font-bold text-primary flex items-center gap-2">
                <Home class="w-6 h-6" /> Kos Online
            </Link>
            <nav class="hidden md:flex gap-4 ml-4">
                <Link :href="route('public.kos.index')" class="text-sm font-medium text-gray-600 hover:text-primary transition-colors">
                    Cari Kos
                </Link>
                <Link v-if="$page.props.auth.user && $page.props.auth.user.role === 'penyewa'" :href="route('tenant.tenancies.index')" class="text-sm font-medium text-gray-600 hover:text-primary transition-colors">
                    Sewa Kos Saya
                </Link>
            </nav>
          </div>
          
          <div class="flex items-center gap-4">
            <template v-if="$page.props.auth.user">
                <!-- Logged In User Menu -->
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" class="gap-2 px-2">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                                <User class="w-4 h-4 text-primary" />
                            </div>
                            <span class="hidden sm:inline-block font-medium">{{ $page.props.auth.user.name }}</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <DropdownMenuLabel>Akun Saya</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        
                        <template v-if="$page.props.auth.user.role === 'super_admin' || $page.props.auth.user.role === 'pemilik_kos'">
                            <Link :href="route('dashboard')">
                                <DropdownMenuItem class="cursor-pointer">
                                    <LayoutDashboard class="w-4 h-4 mr-2" /> Kelola (Admin Panel)
                                </DropdownMenuItem>
                            </Link>
                            <DropdownMenuSeparator />
                        </template>
                        <Link :href="route('profile.edit')">
                            <DropdownMenuItem class="cursor-pointer">
                                <User class="w-4 h-4 mr-2" /> Pengaturan Profil
                            </DropdownMenuItem>
                        </Link>
                        <DropdownMenuSeparator />
                        <Link :href="route('logout')" method="post" as="button" class="w-full">
                            <DropdownMenuItem class="cursor-pointer text-red-600">
                                <LogOut class="w-4 h-4 mr-2" /> Keluar
                            </DropdownMenuItem>
                        </Link>
                    </DropdownMenuContent>
                </DropdownMenu>
            </template>
            <template v-else>
              <Link :href="route('login')">
                <Button variant="outline">Masuk / Daftar</Button>
              </Link>
            </template>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow">
      <slot />
    </main>
    
    <footer class="bg-white border-t py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-500">
            <div class="flex items-center gap-2">
                <Home class="w-5 h-5 text-gray-400" />
                <span class="font-semibold text-gray-900">Kos Online</span>
                &copy; {{ new Date().getFullYear() }}. All rights reserved.
            </div>
            <div class="flex gap-4">
                <Link href="#" class="hover:text-primary">Tentang Kami</Link>
                <Link href="#" class="hover:text-primary">Syarat & Ketentuan</Link>
                <Link href="#" class="hover:text-primary">Kebijakan Privasi</Link>
            </div>
        </div>
    </footer>
    
    <Toaster position="top-right" richColors expand />
  </div>
</template>
