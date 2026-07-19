<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Plus, Edit2, Trash2, AlertTriangle } from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    facilities: Object,
    filters: Object,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    type: 'kos',
    status: 'aktif',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (facility) => {
    isEditing.value = true;
    editingId.value = facility.id;
    form.name = facility.name;
    form.type = facility.type;
    form.status = facility.status;
    form.clearErrors();
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.facilities.update', editingId.value), {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    } else {
        form.post(route('admin.facilities.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
            },
        });
    }
};

const confirmingFacilityDeletion = ref(false);
const facilityToDelete = ref(null);

const confirmFacilityDeletion = (facility) => {
    facilityToDelete.value = facility;
    confirmingFacilityDeletion.value = true;
};

const closeDeleteModal = () => {
    confirmingFacilityDeletion.value = false;
    setTimeout(() => {
        facilityToDelete.value = null;
    }, 300);
};

const deleteFacility = () => {
    if (!facilityToDelete.value) return;
    
    router.delete(route('admin.facilities.destroy', facilityToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};

const handleFilter = (e) => {
    router.get(route('admin.facilities.index'), { type: e.target.value }, { preserveState: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Master Fasilitas" />

        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Master Fasilitas</h2>
                <p class="text-gray-500 mt-1">Kelola data master fasilitas untuk Kos dan Kamar.</p>
            </div>
            <Button @click="openCreateModal">
                <Plus class="w-4 h-4 mr-2" /> Tambah Fasilitas
            </Button>
        </div>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Daftar Fasilitas</CardTitle>
                <div>
                    <Select v-model="filters.type" @update:modelValue="(val) => handleFilter({ target: { value: val } })">
                        <SelectTrigger class="w-[150px]">
                            <SelectValue placeholder="Semua Tipe" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="all">Semua Tipe</SelectItem>
                                <SelectItem value="kos">Kos</SelectItem>
                                <SelectItem value="kamar">Kamar</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3">Nama Fasilitas</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="facility in facilities.data" :key="facility.id" class="border-b">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ facility.name }}</td>
                                <td class="px-4 py-3 capitalize">{{ facility.type }}</td>
                                <td class="px-4 py-3">
                                    <StatusBadge :status="facility.status" />
                                </td>
                                <td class="px-4 py-3 text-right flex justify-end gap-2">
                                    <Button variant="outline" size="sm" @click="openEditModal(facility)">
                                        <Edit2 class="w-4 h-4" />
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="confirmFacilityDeletion(facility)">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="facilities.data.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">Tidak ada data fasilitas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination (simple representation) -->
                <div v-if="facilities.links && facilities.links.length > 3" class="mt-4 flex gap-1">
                    <template v-for="(link, k) in facilities.links" :key="k">
                        <div v-if="link.url === null" class="px-3 py-1 text-sm border rounded text-gray-400 bg-gray-50" v-html="link.label" />
                        <button v-else @click="router.get(link.url, filters, {preserveState: true})" class="px-3 py-1 text-sm border rounded hover:bg-gray-100" :class="{ 'bg-primary text-white border-primary hover:bg-primary': link.active }" v-html="link.label" />
                    </template>
                </div>
            </CardContent>
        </Card>

        <!-- Form Modal -->
        <Dialog :open="isModalOpen" @update:open="val => isModalOpen = val">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Fasilitas' : 'Tambah Fasilitas Baru' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-4 mt-4">
                    <div class="space-y-2">
                        <Label for="name">Nama Fasilitas</Label>
                        <Input id="name" type="text" v-model="form.name" required />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="type">Tipe</Label>
                        <Select id="type" v-model="form.type">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Tipe" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="kos">Kos</SelectItem>
                                    <SelectItem value="kamar">Kamar</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.type" class="text-sm text-destructive">{{ form.errors.type }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="status">Status</Label>
                        <Select id="status" v-model="form.status">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="aktif">Aktif</SelectItem>
                                    <SelectItem value="nonaktif">Nonaktif</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.status" class="text-sm text-destructive">{{ form.errors.status }}</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" @click="isModalOpen = false">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Simpan</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="confirmingFacilityDeletion" @update:open="val => { if(!val) closeDeleteModal(); }">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <div class="flex items-center gap-4 mb-2 text-destructive">
                        <div class="p-3 bg-destructive/10 rounded-full shrink-0">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <DialogTitle>Hapus Fasilitas?</DialogTitle>
                    </div>
                </DialogHeader>
                
                <DialogDescription class="text-sm">
                    Apakah Anda yakin ingin menghapus fasilitas <strong class="text-foreground">{{ facilityToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                </DialogDescription>

                <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                    <Button variant="outline" @click="closeDeleteModal">Batal</Button>
                    <Button variant="destructive" @click="deleteFacility">Ya, Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
