<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { LogOut, User, LayoutDashboard, Building2, Users, ReceiptText, Flag, UserCog, Menu, LayoutList, Landmark, WalletCards } from 'lucide-vue-next';

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
        items.push({ name: 'Dashboard', route: 'admin.dashboard', icon: LayoutDashboard });
        items.push({ name: 'Verifikasi Kos', route: 'admin.verifications.index', icon: Building2 });
        items.push({ name: 'Master Fasilitas', route: 'admin.facilities.index', icon: LayoutList });
        items.push({ name: 'Laporan', route: 'admin.reports.index', icon: Flag });
        items.push({ name: 'Penarikan Pemilik', route: 'admin.withdrawals.index', icon: Landmark });
        items.push({ name: 'Manajemen Pengguna', route: 'admin.users.index', icon: UserCog });
    } else if (role === 'pemilik_kos') {
        items.push({ name: 'Dashboard', route: 'owner.dashboard', icon: LayoutDashboard });
        items.push({ name: 'Properti Kos', route: 'owner.kos.index', icon: Building2 });
        items.push({ name: 'Sewa & Pembayaran', route: 'owner.tenancies.index', icon: Users });
        items.push({ name: 'Saldo & Penarikan', route: 'owner.wallet.index', icon: WalletCards });
        items.push({ name: 'Laporan & Pengaduan', route: 'reports.index', icon: Flag });
    } else if (role === 'penyewa') {
        items.push({ name: 'Beranda Kos', route: 'public.kos.index', icon: LayoutDashboard });
        items.push({ name: 'Sewa & Tagihan', route: 'tenant.tenancies.index', icon: ReceiptText });
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
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar Desktop -->
    <aside class="hidden md:flex flex-col w-64 bg-white border-r fixed h-full z-40">
      <div class="h-16 flex items-center px-6 border-b">
        <Link href="/" class="text-xl font-bold text-primary">Kos Online</Link>
      </div>
      <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <template v-for="item in navItems" :key="item.name">
          <Link :href="route(item.route)" 
                class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors"
                :class="{ 'bg-primary/10 text-primary': isActive(item.route) }">
            <component :is="item.icon" class="w-5 h-5" />
            {{ item.name }}
          </Link>
        </template>
      </nav>
      <div class="p-4 border-t">
        <Link :href="route('logout')" method="post" as="button" class="flex items-center gap-3 px-3 py-2 w-full text-left rounded-md text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
          <LogOut class="w-5 h-5" />
          Logout
        </Link>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col md:pl-64">
      <!-- Topbar -->
      <header class="h-16 bg-white border-b flex items-center justify-between px-4 sm:px-6 z-30 sticky top-0">
        <div class="flex items-center gap-4">
          <Button variant="ghost" size="icon" class="md:hidden" @click="isMobileMenuOpen = !isMobileMenuOpen">
            <Menu class="w-5 h-5" />
          </Button>
          <slot name="header">
            <h1 class="text-lg font-semibold text-gray-900">Dashboard</h1>
          </slot>
        </div>
        <div class="flex items-center gap-4">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" class="gap-2">
                <User class="w-4 h-4" />
                <span class="hidden sm:inline">{{ $page.props.auth.user.name }}</span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
              <div class="px-2 py-1.5 text-sm text-gray-500">
                {{ $page.props.auth.user.email }}
                <div class="capitalize mt-1 text-xs px-2 py-0.5 bg-gray-100 rounded inline-block">{{ $page.props.auth.user.role.replace('_', ' ') }}</div>
              </div>
              <DropdownMenuSeparator />
              <DropdownMenuItem asChild>
                <Link :href="route('profile.edit')" class="w-full cursor-pointer flex items-center">
                  Profil
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <Link :href="route('logout')" method="post" as="button" class="w-full text-left text-red-600 cursor-pointer flex items-center">
                  Logout
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8">
        <slot />
      </main>
    </div>
    
    <!-- Toast Notifications -->
    <Toaster position="top-right" richColors expand />
  </div>
</template>
