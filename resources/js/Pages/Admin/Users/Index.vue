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
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen Pengguna</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data penyewa, pemilik kos, dan admin.</p>
                </div>
                <Button @click="openCreate"><Plus class="w-4 h-4 mr-2" /> Tambah Pengguna</Button>
            </div>

            <Card>
                <CardHeader>
                    <div class="flex flex-col md:flex-row gap-4 justify-between md:items-center">
                        <CardTitle>Daftar Pengguna</CardTitle>
                        <div class="flex items-center gap-2">
                            <Select v-model="roleFilter" @update:modelValue="doSearch">
                                <SelectTrigger class="w-[150px]">
                                    <SelectValue placeholder="Filter Peran" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Peran</SelectItem>
                                    <SelectItem value="penyewa">Penyewa</SelectItem>
                                    <SelectItem value="pemilik_kos">Pemilik Kos</SelectItem>
                                    <SelectItem value="super_admin">Super Admin</SelectItem>
                                </SelectContent>
                            </Select>
                            <div class="relative w-64">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
                                <Input v-model="search" @keyup.enter="doSearch" placeholder="Cari nama atau email..." class="pl-8" />
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead>Email & WA</TableHead>
                                <TableHead>Peran</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in users.data" :key="user.id">
                                <TableCell class="font-medium">{{ user.name }}</TableCell>
                                <TableCell>
                                    <div>{{ user.email }}</div>
                                    <div class="text-xs text-gray-500">{{ user.whatsapp_number || '-' }}</div>
                                </TableCell>
                                <TableCell>
                                    <span class="capitalize">{{ user.role.replace('_', ' ') }}</span>
                                </TableCell>
                                <TableCell>
                                    <StatusBadge :status="user.status" />
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="icon" @click="openEdit(user)">
                                            <Edit class="w-4 h-4" />
                                        </Button>
                                        <Button variant="destructive" size="icon" @click="confirmDelete(user)" :disabled="user.id === $page.props.auth.user.id">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div class="mt-4 flex justify-center" v-if="users.links.length > 3">
                        <div class="flex gap-1">
                            <template v-for="(link, i) in users.links" :key="i">
                                <Link 
                                    v-if="link.url"
                                    :href="link.url" 
                                    class="px-3 py-1 border rounded-md text-sm"
                                    :class="link.active ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 hover:bg-gray-50'"
                                    v-html="link.label"
                                />
                                <span v-else class="px-3 py-1 border rounded-md text-sm text-gray-400 bg-gray-50" v-html="link.label"></span>
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Modal Tambah/Edit Pengguna -->
            <Dialog :open="showModal" @update:open="(v) => { if(!v) showModal = false }">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>{{ editingUser ? 'Edit Pengguna' : 'Tambah Pengguna' }}</DialogTitle>
                    </DialogHeader>
                    
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Nama Lengkap</Label>
                            <Input id="name" v-model="form.name" required />
                            <p class="text-xs text-red-500" v-if="form.errors.name">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" type="email" v-model="form.email" required />
                            <p class="text-xs text-red-500" v-if="form.errors.email">{{ form.errors.email }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="whatsapp_number">No. WhatsApp</Label>
                            <Input id="whatsapp_number" v-model="form.whatsapp_number" />
                            <p class="text-xs text-red-500" v-if="form.errors.whatsapp_number">{{ form.errors.whatsapp_number }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="password">Password {{ editingUser ? '(Kosongkan jika tidak diubah)' : '' }}</Label>
                            <Input id="password" type="password" v-model="form.password" :required="!editingUser" />
                            <p class="text-xs text-red-500" v-if="form.errors.password">{{ form.errors.password }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="role">Peran</Label>
                            <Select v-model="form.role">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Peran" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="penyewa">Penyewa</SelectItem>
                                    <SelectItem value="pemilik_kos">Pemilik Kos</SelectItem>
                                    <SelectItem value="super_admin">Super Admin</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-red-500" v-if="form.errors.role">{{ form.errors.role }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="aktif">Aktif</SelectItem>
                                    <SelectItem value="nonaktif">Nonaktif</SelectItem>
                                    <SelectItem value="diblokir">Diblokir</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-red-500" v-if="form.errors.status">{{ form.errors.status }}</p>
                        </div>
                        
                        <DialogFooter class="pt-4">
                            <Button type="button" variant="outline" @click="showModal = false">Batal</Button>
                            <Button type="submit" :disabled="form.processing">Simpan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Modal Hapus Pengguna -->
            <Dialog :open="showDeleteModal" @update:open="(v) => { if(!v) showDeleteModal = false }">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <div class="flex items-center gap-4 mb-2 text-destructive">
                            <div class="p-3 bg-destructive/10 rounded-full shrink-0">
                                <AlertTriangle class="w-6 h-6" />
                            </div>
                            <DialogTitle>Hapus Pengguna</DialogTitle>
                        </div>
                    </DialogHeader>

                    <DialogDescription class="text-sm">
                        Apakah Anda yakin ingin menghapus pengguna <span class="font-bold">{{ userToDelete?.name }}</span>?
                        Tindakan ini tidak dapat dibatalkan dan semua data yang terkait akan dihapus secara permanen.
                    </DialogDescription>

                    <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                        <Button variant="outline" @click="showDeleteModal = false">Batal</Button>
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
