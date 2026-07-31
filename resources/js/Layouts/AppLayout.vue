<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { LogOut, User, LayoutDashboard, Building2, Users, ReceiptText, Flag, UserCog, Menu, Landmark, WalletCards, MessageSquare, Smartphone, Moon, Sun, Settings, Database } from 'lucide-vue-next';
import { useDark, useToggle } from '@vueuse/core';

const isDark = useDark();
const toggleDark = useToggle(isDark);

const isMobileMenuOpen = ref(false);

import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';

const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        toast.success(flash.success);
    }
    if (flash?.error) {
        toast.error(flash.error);
    }
    if (flash?.status) {
        toast.info(flash.status);
    }
}, { deep: true, immediate: true });

const navItems = computed(() => {
    const role = userRole.value;
    const items = [];

    if (role === 'super_admin') {
        items.push({ name: 'Dashboard', route: 'superadmin.dashboard', icon: LayoutDashboard });
        items.push({ name: 'Verifikasi Kos', route: 'superadmin.verifications.index', icon: Building2 });
        items.push({ name: 'Master Data', route: 'superadmin.master-data.index', icon: Database });
        items.push({ name: 'Laporan', route: 'superadmin.reports.index', icon: Flag });
        items.push({ name: 'Penarikan Dana', route: 'superadmin.withdrawals.index', icon: Landmark });
        items.push({ name: 'Manajemen Pengguna', route: 'superadmin.users.index', icon: UserCog });
        items.push({ name: 'Pengaturan Sistem', route: 'superadmin.settings.index', icon: Settings });
    } else if (role === 'admin') {
        items.push({ name: 'Dashboard', route: 'admin.dashboard', icon: LayoutDashboard });
        items.push({ name: 'Properti Kos', route: 'admin.kos.index', icon: Building2 });
        items.push({ name: 'Sewa & Pembayaran', route: 'admin.tenancies.index', icon: Users });
        items.push({ name: 'Saldo & Penarikan', route: 'admin.wallet.index', icon: WalletCards });
        items.push({ name: 'Ulasan Penyewa', route: 'admin.reviews.index', icon: MessageSquare });
        items.push({ name: 'Pengaturan WhatsApp', route: 'admin.whatsapp.index', icon: Smartphone });
    } else if (role === 'user') {
        items.push({ name: 'Beranda Kos', route: 'public.kos.index', icon: LayoutDashboard });
        items.push({ name: 'Sewa & Tagihan', route: 'user.tenancies.index', icon: ReceiptText });
        items.push({ name: 'Laporan & Pengaduan', route: 'reports.index', icon: Flag });
    }

    return items;
});

const isActive = (routeName) => {
    // Exact match
    if (route().current(routeName)) return true;
    
    // If it's an index route, match its nested routes (e.g., owner.kos.index -> owner.kos.*)
    if (routeName.endsWith('.index')) {
        const baseRoute = routeName.replace(/\.index$/, '');
        return route().current(baseRoute + '.*');
    }
    
    return false;
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-slate-950 flex transition-colors duration-300">
    <!-- Sidebar Desktop -->
    <aside class="hidden md:flex flex-col w-64 bg-white dark:bg-slate-900 border-r dark:border-slate-800 fixed h-full z-40 transition-colors duration-300">
      <div class="h-16 flex items-center px-6 border-b dark:border-slate-800">
        <Link href="/" class="text-xl font-bold text-primary dark:text-blue-400 flex items-center gap-2">
            <img v-if="$page.props.appSettings?.app_logo" :src="'/storage/' + $page.props.appSettings.app_logo" alt="Logo" class="h-8 w-auto" />
            <span>{{ $page.props.appSettings?.app_name || 'Kos Online' }}</span>
        </Link>
      </div>
      <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <template v-for="item in navItems" :key="item.name">
          <Link :href="route(item.route)" 
                class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-slate-800 dark:text-slate-300 transition-colors"
                :class="{ 'bg-primary/10 text-primary dark:bg-blue-900/50 dark:text-blue-400': isActive(item.route) }">
            <component :is="item.icon" class="w-5 h-5" />
            {{ item.name }}
          </Link>
        </template>
      </nav>
      <div class="p-4 border-t dark:border-slate-800">
        <Link :href="route('logout')" method="post" as="button" class="flex items-center gap-3 px-3 py-2 w-full text-left rounded-md text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors">
          <LogOut class="w-5 h-5" />
          Logout
        </Link>
      </div>
    </aside>

    <!-- Sidebar Mobile Overlay -->
    <div v-if="isMobileMenuOpen" class="fixed inset-0 bg-black/50 z-40 md:hidden transition-opacity" @click="isMobileMenuOpen = false"></div>
    
    <!-- Sidebar Mobile -->
    <aside :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full'" class="md:hidden flex flex-col w-64 bg-white dark:bg-slate-900 border-r dark:border-slate-800 fixed h-full z-50 transition-transform duration-300 ease-in-out top-0 left-0">
      <div class="h-16 flex items-center justify-between px-6 border-b dark:border-slate-800">
        <Link href="/" class="text-xl font-bold text-primary dark:text-blue-400 flex items-center gap-2">
            <img v-if="$page.props.appSettings?.app_logo" :src="'/storage/' + $page.props.appSettings.app_logo" alt="Logo" class="h-8 w-auto" />
            <span>{{ $page.props.appSettings?.app_name || 'Kos Online' }}</span>
        </Link>
        <button @click="isMobileMenuOpen = false" class="text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
      <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <template v-for="item in navItems" :key="item.name">
          <Link :href="route(item.route)" 
                @click="isMobileMenuOpen = false"
                class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 dark:hover:bg-slate-800 dark:text-slate-300 transition-colors"
                :class="{ 'bg-primary/10 text-primary dark:bg-blue-900/50 dark:text-blue-400': isActive(item.route) }">
            <component :is="item.icon" class="w-5 h-5" />
            {{ item.name }}
          </Link>
        </template>
      </nav>
      <div class="p-4 border-t dark:border-slate-800">
        <Link :href="route('logout')" method="post" as="button" @click="isMobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2 w-full text-left rounded-md text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 transition-colors">
          <LogOut class="w-5 h-5" />
          Logout
        </Link>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col md:pl-64">
      <!-- Topbar -->
      <header class="h-16 bg-white dark:bg-slate-900 border-b dark:border-slate-800 flex items-center justify-between px-4 sm:px-6 z-30 sticky top-0 transition-colors duration-300">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="icon" class="md:hidden dark:text-slate-300" @click="isMobileMenuOpen = !isMobileMenuOpen">
            <Menu class="w-5 h-5" />
          </Button>
          <slot name="header">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Dashboard</h1>
          </slot>
        </div>
        <div class="flex items-center gap-2">
          <!-- Dark Mode Toggle -->
          <Button variant="ghost" size="icon" @click="toggleDark()">
            <Sun v-if="isDark" class="w-5 h-5 text-yellow-500" />
            <Moon v-else class="w-5 h-5 text-slate-500" />
          </Button>
          
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" class="gap-2 dark:text-slate-200">
                <User class="w-4 h-4" />
                <span class="hidden sm:inline">{{ $page.props.auth.user.name }}</span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56 dark:bg-slate-800 dark:border-slate-700">
              <div class="px-2 py-1.5 text-sm text-gray-500 dark:text-slate-400">
                {{ $page.props.auth.user.email }}
                <div class="capitalize mt-1 text-xs px-2 py-0.5 bg-gray-100 dark:bg-slate-700 rounded inline-block">{{ $page.props.auth.user.role.replace('_', ' ') }}</div>
              </div>
              <DropdownMenuSeparator class="dark:bg-slate-700" />
              <DropdownMenuItem asChild class="dark:focus:bg-slate-700 dark:text-slate-200">
                <Link :href="route('profile.edit')" class="w-full cursor-pointer flex items-center">
                  Profil
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild class="dark:focus:bg-slate-700">
                <Link :href="route('logout')" method="post" as="button" class="w-full text-left text-red-600 dark:text-red-400 cursor-pointer flex items-center">
                  Logout
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 text-slate-900 dark:text-slate-100 transition-colors duration-300">
        <slot />
      </main>
    </div>
    
    <!-- Toast Notifications -->
    <Toaster position="top-right" richColors expand />
  </div>
</template>
