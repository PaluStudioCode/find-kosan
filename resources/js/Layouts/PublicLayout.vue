<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import { watch, ref, onMounted, onUnmounted } from 'vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { User, LogOut, FileText, LayoutDashboard, Home } from 'lucide-vue-next';

const props = defineProps({
    landing: {
        type: Boolean,
        default: false,
    },
    hideFooter: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const activeSection = ref('');
let observer = null;

onMounted(() => {
    if (props.landing) {
        const sections = document.querySelectorAll('section[id]');
        
        observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    activeSection.value = entry.target.id;
                }
            });
        }, {
            rootMargin: '-30% 0px -70% 0px',
            threshold: 0
        });

        sections.forEach(section => {
            observer.observe(section);
        });
    }
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

watch(() => page.props.flash, (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
    if (flash?.status) toast.info(flash.status);
}, { deep: true, immediate: true });

const scrollToSection = (id) => {
    const element = document.querySelector(id);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <header class="bg-white/90 border-b border-gray-100 backdrop-blur-xl sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <div class="flex items-center gap-6">
            <Link href="/" class="text-xl font-bold text-primary flex items-center gap-2">
                <Home class="w-6 h-6" /> Kos Online
            </Link>
            <nav class="hidden md:flex gap-4 ml-4">
                <template v-if="landing">
                    <a href="#peta-kos" @click.prevent="scrollToSection('#peta-kos')" :class="['text-sm transition-colors', activeSection === 'peta-kos' ? 'text-primary font-semibold' : 'text-gray-600 hover:text-primary font-medium']">Peta Kos</a>
                    <a href="#cara-kerja" @click.prevent="scrollToSection('#cara-kerja')" :class="['text-sm transition-colors', activeSection === 'cara-kerja' ? 'text-primary font-semibold' : 'text-gray-600 hover:text-primary font-medium']">Cara Kerja</a>
                    <a href="#keamanan" @click.prevent="scrollToSection('#keamanan')" :class="['text-sm transition-colors', activeSection === 'keamanan' ? 'text-primary font-semibold' : 'text-gray-600 hover:text-primary font-medium']">Keamanan</a>
                    <a href="#pemilik" @click.prevent="scrollToSection('#pemilik')" :class="['text-sm transition-colors', activeSection === 'pemilik' ? 'text-primary font-semibold' : 'text-gray-600 hover:text-primary font-medium']">Untuk Pemilik</a>
                    <a href="#ulasan" @click.prevent="scrollToSection('#ulasan')" :class="['text-sm transition-colors', activeSection === 'ulasan' ? 'text-primary font-semibold' : 'text-gray-600 hover:text-primary font-medium']">Ulasan</a>
                </template>
                <template v-else>
                    <Link :href="route('public.kos.index')" :class="route().current('public.kos.*') ? 'text-primary font-semibold' : 'text-gray-600 hover:text-primary'" class="text-sm transition-colors">
                        Cari Kos
                    </Link>
                    <Link v-if="$page.props.auth.user && $page.props.auth.user.role === 'penyewa'" :href="route('tenant.tenancies.index')" :class="route().current('tenant.tenancies.*') ? 'text-primary font-semibold' : 'text-gray-600 hover:text-primary'" class="text-sm transition-colors">
                        Sewa Kos Saya
                    </Link>
                </template>
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
    
    <footer v-if="!hideFooter" class="bg-white border-t py-12">
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
