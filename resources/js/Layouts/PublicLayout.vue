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
    <header class="bg-white/80 border-b border-slate-200 backdrop-blur-lg sticky top-0 z-50 transition-all">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <div class="flex items-center gap-8">
            <Link href="/" class="text-xl font-extrabold text-slate-900 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                    <Home class="w-4 h-4" />
                </span>
                Kos Online
            </Link>
            
            <nav class="hidden md:flex gap-6 items-center">
                <template v-if="landing">
                    <a href="#peta-kos" @click.prevent="scrollToSection('#peta-kos')" :class="['text-sm transition-all', activeSection === 'peta-kos' ? 'text-teal-600 font-bold' : 'text-slate-500 hover:text-slate-900 font-medium']">Peta Kos</a>
                    <a href="#cara-kerja" @click.prevent="scrollToSection('#cara-kerja')" :class="['text-sm transition-all', activeSection === 'cara-kerja' ? 'text-teal-600 font-bold' : 'text-slate-500 hover:text-slate-900 font-medium']">Cara Kerja</a>
                    <a href="#keamanan" @click.prevent="scrollToSection('#keamanan')" :class="['text-sm transition-all', activeSection === 'keamanan' ? 'text-teal-600 font-bold' : 'text-slate-500 hover:text-slate-900 font-medium']">Keamanan</a>
                    <a href="#pemilik" @click.prevent="scrollToSection('#pemilik')" :class="['text-sm transition-all', activeSection === 'pemilik' ? 'text-teal-600 font-bold' : 'text-slate-500 hover:text-slate-900 font-medium']">Untuk Pemilik</a>
                    <a href="#ulasan" @click.prevent="scrollToSection('#ulasan')" :class="['text-sm transition-all', activeSection === 'ulasan' ? 'text-teal-600 font-bold' : 'text-slate-500 hover:text-slate-900 font-medium']">Ulasan</a>
                </template>
                <template v-else>
                    <Link :href="route('public.kos.index')" :class="route().current('public.kos.*') ? 'text-teal-600 font-bold' : 'text-slate-500 hover:text-slate-900 font-medium'" class="text-sm transition-all">
                        Cari Kos
                    </Link>
                    <Link v-if="$page.props.auth.user && $page.props.auth.user.role === 'penyewa'" :href="route('tenant.tenancies.index')" :class="route().current('tenant.tenancies.*') ? 'text-teal-600 font-bold' : 'text-slate-500 hover:text-slate-900 font-medium'" class="text-sm transition-all">
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
                        <Button variant="ghost" class="gap-2 px-2 hover:bg-slate-50 rounded-full">
                            <div class="w-8 h-8 rounded-full bg-teal-50 flex items-center justify-center">
                                <User class="w-4 h-4 text-teal-600" />
                            </div>
                            <span class="hidden sm:inline-block font-bold text-slate-700 pr-2">{{ $page.props.auth.user.name }}</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56 rounded-xl shadow-lg border-slate-100">
                        <DropdownMenuLabel class="font-bold text-slate-900">Akun Saya</DropdownMenuLabel>
                        <DropdownMenuSeparator class="bg-slate-100" />
                        
                        <template v-if="$page.props.auth.user.role === 'super_admin' || $page.props.auth.user.role === 'pemilik_kos'">
                            <Link :href="route('dashboard')">
                                <DropdownMenuItem class="cursor-pointer hover:bg-slate-50 focus:bg-slate-50">
                                    <LayoutDashboard class="w-4 h-4 mr-2 text-slate-500" /> Kelola (Admin Panel)
                                </DropdownMenuItem>
                            </Link>
                            <DropdownMenuSeparator class="bg-slate-100" />
                        </template>
                        <Link :href="route('profile.edit')">
                            <DropdownMenuItem class="cursor-pointer hover:bg-slate-50 focus:bg-slate-50">
                                <User class="w-4 h-4 mr-2 text-slate-500" /> Pengaturan Profil
                            </DropdownMenuItem>
                        </Link>
                        <DropdownMenuSeparator class="bg-slate-100" />
                        <Link :href="route('logout')" method="post" as="button" class="w-full">
                            <DropdownMenuItem class="cursor-pointer text-red-600 focus:text-red-700 focus:bg-red-50 hover:bg-red-50">
                                <LogOut class="w-4 h-4 mr-2" /> Keluar
                            </DropdownMenuItem>
                        </Link>
                    </DropdownMenuContent>
                </DropdownMenu>
            </template>
            <template v-else>
              <Link :href="route('login')">
                <Button variant="outline" class="border-slate-200 text-slate-700 hover:bg-slate-50 font-bold rounded-full px-6 transition-colors shadow-sm">Masuk / Daftar</Button>
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
