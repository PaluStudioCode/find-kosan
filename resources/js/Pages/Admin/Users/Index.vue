<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Edit, Trash2, Plus, Search, AlertTriangle, Loader2 } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || 'all');

const doSearch = () => {
    router.get(route('admin.users.index'), {
        search: search.value,
        role: roleFilter.value !== 'all' ? roleFilter.value : null
    }, { preserveState: true });
};

const showModal = ref(false);
const editingUser = ref(null);
const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'penyewa',
    status: 'aktif',
    whatsapp_number: ''
});

const openCreate = () => {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.role = user.role;
    form.status = user.status;
    form.whatsapp_number = user.whatsapp_number || '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editingUser.value) {
        form.put(route('admin.users.update', editingUser.value.id), {
            onSuccess: () => {
                showModal.value = false;
            }
        });
    } else {
        form.post(route('admin.users.store'), {
            onSuccess: () => {
                showModal.value = false;
            }
        });
    }
};

const showDeleteModal = ref(false);
const userToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if(!userToDelete.value) return;
    
    isDeleting.value = true;
    router.delete(route('admin.users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onError: () => {
            showDeleteModal.value = false;
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Manajemen Pengguna" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Pengguna</h2>
                    <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">Kelola data penyewa, pemilik kos, dan admin.</p>
                </div>
                <Button @click="openCreate"><Plus class="w-4 h-4 mr-2" /> Tambah Pengguna</Button>
            </div>

            <Card class="border-0 dark:bg-slate-900 shadow-sm dark:border-slate-800">
                <CardHeader class="border-b dark:border-slate-800">
                    <div class="flex flex-col md:flex-row gap-4 justify-between md:items-center">
                        <CardTitle class="dark:text-white">Daftar Pengguna</CardTitle>
                        <div class="flex items-center gap-2">
                            <Select v-model="roleFilter" @update:modelValue="doSearch">
                                <SelectTrigger class="w-[150px] dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                                    <SelectValue placeholder="Filter Peran" />
                                </SelectTrigger>
                                <SelectContent class="dark:bg-slate-800 dark:border-slate-700">
                                    <SelectItem value="all" class="dark:text-slate-200 dark:focus:bg-slate-700">Semua Peran</SelectItem>
                                    <SelectItem value="penyewa" class="dark:text-slate-200 dark:focus:bg-slate-700">Penyewa</SelectItem>
                                    <SelectItem value="pemilik_kos" class="dark:text-slate-200 dark:focus:bg-slate-700">Pemilik Kos</SelectItem>
                                    <SelectItem value="super_admin" class="dark:text-slate-200 dark:focus:bg-slate-700">Super Admin</SelectItem>
                                </SelectContent>
                            </Select>
                            <div class="relative w-64">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500 dark:text-slate-400" />
                                <Input v-model="search" @keyup.enter="doSearch" placeholder="Cari nama atau email..." class="pl-8 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500" />
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0 border-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="dark:border-slate-800 bg-gray-50 dark:bg-slate-800/50">
                                <TableHead class="dark:text-slate-400">Nama</TableHead>
                                <TableHead class="dark:text-slate-400">Email & WA</TableHead>
                                <TableHead class="dark:text-slate-400">Peran</TableHead>
                                <TableHead class="dark:text-slate-400">Status</TableHead>
                                <TableHead class="text-right dark:text-slate-400">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in users.data" :key="user.id" class="dark:border-slate-800 dark:hover:bg-slate-800/30 transition-colors">
                                <TableCell class="font-medium dark:text-slate-200">{{ user.name }}</TableCell>
                                <TableCell>
                                    <div class="dark:text-slate-300">{{ user.email }}</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-500">{{ user.whatsapp_number || '-' }}</div>
                                </TableCell>
                                <TableCell>
                                    <span class="capitalize dark:text-slate-400">{{ user.role.replace('_', ' ') }}</span>
                                </TableCell>
                                <TableCell>
                                    <StatusBadge :status="user.status" />
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="icon" @click="openEdit(user)" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                            <Edit class="w-4 h-4" />
                                        </Button>
                                        <Button variant="destructive" size="icon" @click="confirmDelete(user)" :disabled="user.id === $page.props.auth.user.id">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="users.data.length === 0">
                                <TableCell colspan="5" class="text-center py-10 text-gray-500 dark:text-slate-500 border-0">
                                    Tidak ada pengguna ditemukan.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div class="p-4 flex justify-center bg-gray-50 dark:bg-slate-900 rounded-b-lg border-t dark:border-slate-800" v-if="users.links && users.links.length > 3">
                        <div class="flex gap-1">
                            <template v-for="(link, i) in users.links" :key="i">
                                <Link 
                                    v-if="link.url"
                                    :href="link.url" 
                                    class="px-3 py-1 border rounded-md text-sm transition-colors"
                                    :class="link.active ? 'bg-primary text-white border-primary dark:bg-blue-600 dark:text-white dark:border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700'"
                                    v-html="link.label"
                                />
                                <span v-else class="px-3 py-1 border rounded-md text-sm text-gray-400 dark:text-slate-500 bg-gray-50 dark:bg-slate-800 dark:border-slate-700" v-html="link.label"></span>
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Modal Tambah/Edit Pengguna -->
            <Dialog :open="showModal" @update:open="(v) => { if(!v) showModal = false }">
                <DialogContent class="sm:max-w-[425px] dark:bg-slate-900 dark:border-slate-800">
                    <DialogHeader>
                        <DialogTitle class="dark:text-white">{{ editingUser ? 'Edit Pengguna' : 'Tambah Pengguna' }}</DialogTitle>
                    </DialogHeader>
                    
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name" class="dark:text-slate-300">Nama Lengkap</Label>
                            <Input id="name" v-model="form.name" required class="dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                            <p class="text-xs text-red-500 dark:text-red-400" v-if="form.errors.name">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="email" class="dark:text-slate-300">Email</Label>
                            <Input id="email" type="email" v-model="form.email" required class="dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                            <p class="text-xs text-red-500 dark:text-red-400" v-if="form.errors.email">{{ form.errors.email }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="whatsapp_number" class="dark:text-slate-300">No. WhatsApp</Label>
                            <Input id="whatsapp_number" v-model="form.whatsapp_number" class="dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                            <p class="text-xs text-red-500 dark:text-red-400" v-if="form.errors.whatsapp_number">{{ form.errors.whatsapp_number }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="password" class="dark:text-slate-300">Password {{ editingUser ? '(Kosongkan jika tidak diubah)' : '' }}</Label>
                            <Input id="password" type="password" v-model="form.password" :required="!editingUser" class="dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
                            <p class="text-xs text-red-500 dark:text-red-400" v-if="form.errors.password">{{ form.errors.password }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="role" class="dark:text-slate-300">Peran</Label>
                            <Select v-model="form.role">
                                <SelectTrigger class="dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                                    <SelectValue placeholder="Pilih Peran" />
                                </SelectTrigger>
                                <SelectContent class="dark:bg-slate-800 dark:border-slate-700">
                                    <SelectItem value="penyewa" class="dark:text-slate-200 dark:focus:bg-slate-700">Penyewa</SelectItem>
                                    <SelectItem value="pemilik_kos" class="dark:text-slate-200 dark:focus:bg-slate-700">Pemilik Kos</SelectItem>
                                    <SelectItem value="super_admin" class="dark:text-slate-200 dark:focus:bg-slate-700">Super Admin</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-red-500 dark:text-red-400" v-if="form.errors.role">{{ form.errors.role }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="status" class="dark:text-slate-300">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger class="dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                                    <SelectValue placeholder="Pilih Status" />
                                </SelectTrigger>
                                <SelectContent class="dark:bg-slate-800 dark:border-slate-700">
                                    <SelectItem value="aktif" class="dark:text-slate-200 dark:focus:bg-slate-700">Aktif</SelectItem>
                                    <SelectItem value="nonaktif" class="dark:text-slate-200 dark:focus:bg-slate-700">Nonaktif</SelectItem>
                                    <SelectItem value="diblokir" class="dark:text-slate-200 dark:focus:bg-slate-700">Diblokir</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-red-500 dark:text-red-400" v-if="form.errors.status">{{ form.errors.status }}</p>
                        </div>
                        
                        <DialogFooter class="pt-4 gap-2 sm:gap-0">
                            <Button type="button" variant="outline" @click="showModal = false" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Button>
                            <Button type="submit" :disabled="form.processing">Simpan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Modal Hapus Pengguna -->
            <Dialog :open="showDeleteModal" @update:open="(v) => { if(!v) showDeleteModal = false }">
                <DialogContent class="sm:max-w-[425px] dark:bg-slate-900 dark:border-slate-800">
                    <DialogHeader>
                        <div class="flex items-center gap-4 mb-2 text-destructive dark:text-red-400">
                            <div class="p-3 bg-destructive/10 dark:bg-red-900/30 rounded-full shrink-0">
                                <AlertTriangle class="w-6 h-6" />
                            </div>
                            <DialogTitle>Hapus Pengguna</DialogTitle>
                        </div>
                    </DialogHeader>

                    <DialogDescription class="text-sm dark:text-slate-400">
                        Apakah Anda yakin ingin menghapus pengguna <span class="font-bold text-gray-900 dark:text-white">{{ userToDelete?.name }}</span>?
                        Tindakan ini tidak dapat dibatalkan dan semua data yang terkait akan dihapus secara permanen.
                    </DialogDescription>

                    <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                        <Button variant="outline" @click="showDeleteModal = false" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Button>
                        <Button variant="destructive" :disabled="isDeleting" @click="executeDelete">
                            <Loader2 v-if="isDeleting" class="w-4 h-4 mr-2 animate-spin" />
                            Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
