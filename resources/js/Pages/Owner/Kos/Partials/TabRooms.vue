<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogScrollContent } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/toast/use-toast';
import { Plus, Edit, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    kos: Object,
    facilities: Array,
    isLocked: Boolean
});

const { toast } = useToast();
const isEditing = ref(false);
const editingRoomId = ref(null);
const showModal = ref(false);

const form = useForm({
    name: '',
    room_number: '',
    description: '',
    price: '',
    price_period: 'bulanan',
    capacity: 1,
    status: 'tersedia',
    facilities: []
});

const resetForm = () => {
    isEditing.value = false;
    editingRoomId.value = null;
    form.reset();
    form.clearErrors();
};

const openAddModal = () => {
    resetForm();
    showModal.value = true;
};

const openEditModal = (room) => {
    isEditing.value = true;
    editingRoomId.value = room.id;
    form.name = room.name;
    form.room_number = room.room_number;
    form.description = room.description;
    form.price = room.price;
    form.price_period = room.price_period;
    form.capacity = room.capacity;
    form.status = room.status;
    form.facilities = room.facilities ? room.facilities.map(f => f.id) : [];
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('owner.kos.rooms.update', { kos: props.kos.id, room: editingRoomId.value }), {
            preserveScroll: true,
            onSuccess: () => {
                // Notifikasi sukses ditangani oleh layout global (flash.success)
                closeModal();
            },
            onError: () => {
                toast({ title: 'Gagal', description: 'Periksa kembali data kamar Anda.', variant: 'destructive' });
            }
        });
    } else {
        form.post(route('owner.kos.rooms.store', props.kos.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Notifikasi sukses ditangani oleh layout global (flash.success)
                closeModal();
            },
            onError: () => {
                toast({ title: 'Gagal', description: 'Periksa kembali data kamar Anda.', variant: 'destructive' });
            }
        });
    }
};

const deleteRoom = (roomId) => {
    if (confirm('Apakah Anda yakin ingin menghapus kamar ini?')) {
        router.delete(route('owner.kos.rooms.destroy', { kos: props.kos.id, room: roomId }), {
            preserveScroll: true,
            onSuccess: () => {
                // Notifikasi sukses ditangani oleh layout global (flash.success)
            },
            onError: (err) => {
                if (err.error) {
                    toast({ title: 'Gagal', description: err.error, variant: 'destructive' });
                }
            }
        });
    }
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
};
</script>

<template>
    <div class="space-y-6">
        <div v-if="isLocked" class="bg-orange-50 border border-orange-200 text-orange-800 p-4 rounded-lg flex items-start mb-6">
            <div class="mr-3 mt-0.5 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            </div>
            <div>
                <h4 class="font-semibold">Data Sedang Ditinjau</h4>
                <p class="text-sm mt-1">Anda tidak dapat menambah, mengubah, atau menghapus tipe kamar selama proses peninjauan oleh Super Admin berlangsung.</p>
            </div>
        </div>

        <div class="flex justify-between items-center border-b pb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Tipe Kamar</h3>
                <p class="text-sm text-gray-500 mt-1">Kelola tipe-tipe kamar yang tersedia di kos ini beserta harga dan fasilitasnya.</p>
            </div>
            <Button v-if="!isLocked" @click="openAddModal">
                <Plus class="w-4 h-4 mr-2" /> Tambah Kamar
            </Button>
        </div>

        <!-- Table -->
        <div class="border rounded-lg overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nomor / Nama</TableHead>
                        <TableHead>Harga</TableHead>
                        <TableHead>Kapasitas</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="!kos.rooms || kos.rooms.length === 0">
                        <TableCell colspan="5" class="text-center h-24 text-gray-500">Belum ada kamar.</TableCell>
                    </TableRow>
                    <TableRow v-for="room in kos.rooms" :key="room.id">
                        <TableCell>
                            <div class="font-semibold">{{ room.room_number }}</div>
                            <div class="text-sm text-gray-500">{{ room.name }}</div>
                        </TableCell>
                        <TableCell>
                            {{ formatPrice(room.price) }} <span class="text-xs text-gray-500">/ {{ room.price_period }}</span>
                        </TableCell>
                        <TableCell>{{ room.capacity }} orang</TableCell>
                        <TableCell>
                            <span class="px-2 py-1 text-xs rounded-full border" 
                                  :class="{
                                      'bg-green-50 text-green-700 border-green-200': room.status === 'tersedia',
                                      'bg-blue-50 text-blue-700 border-blue-200': room.status === 'disewa',
                                      'bg-red-50 text-red-700 border-red-200': room.status === 'penuh',
                                      'bg-gray-50 text-gray-700 border-gray-200': room.status === 'dalam_perbaikan'
                                  }">
                                {{ room.status }}
                            </span>
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <Button variant="outline" size="sm" @click="openEditModal(room)" :disabled="isLocked">
                                    <Edit class="w-4 h-4 mr-1" /> Edit
                                </Button>
                                <Button variant="outline" size="sm" class="text-red-500 hover:text-red-700 hover:bg-red-50" @click="deleteRoom(room.id)" :disabled="isLocked">
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Modal Form Add/Edit -->
        <Dialog :open="showModal" @update:open="(val) => { if (!val) closeModal(); }">
            <DialogScrollContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Kamar' : 'Tambah Kamar Baru' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Perbarui informasi kamar yang sudah ada.' : 'Masukkan informasi kamar baru untuk properti Anda.' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4 py-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="r_name">Nama Tipe Kamar <span class="text-red-500">*</span></Label>
                            <Input id="r_name" v-model="form.name" placeholder="Misal: Kamar Standar / Kamar AC" required />
                            <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="r_room_number">Nomor Kamar <span class="text-red-500">*</span></Label>
                            <Input id="r_room_number" v-model="form.room_number" placeholder="Misal: A1, B2" required />
                            <p v-if="form.errors.room_number" class="text-sm text-red-500">{{ form.errors.room_number }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="r_description">Deskripsi <span class="text-red-500">*</span></Label>
                        <Textarea id="r_description" v-model="form.description" rows="2" required />
                        <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <Label for="r_price">Harga <span class="text-red-500">*</span></Label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                                <Input id="r_price" type="number" v-model="form.price" class="pl-10" required />
                            </div>
                            <p v-if="form.errors.price" class="text-sm text-red-500">{{ form.errors.price }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="r_price_period">Periode Harga</Label>
                            <select id="r_price_period" v-model="form.price_period" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="harian">Harian</option>
                                <option value="mingguan">Mingguan</option>
                                <option value="bulanan">Bulanan</option>
                                <option value="tahunan">Tahunan</option>
                            </select>
                            <p v-if="form.errors.price_period" class="text-sm text-red-500">{{ form.errors.price_period }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="r_capacity">Kapasitas (Orang) <span class="text-red-500">*</span></Label>
                            <Input id="r_capacity" type="number" min="1" v-model="form.capacity" required />
                            <p v-if="form.errors.capacity" class="text-sm text-red-500">{{ form.errors.capacity }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="r_status">Status Kamar</Label>
                        <select id="r_status" v-model="form.status" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <option value="tersedia">Tersedia</option>
                            <option value="penuh">Penuh</option>
                            <option value="disewa">Disewa</option>
                            <option value="dalam_perbaikan">Dalam Perbaikan</option>
                        </select>
                        <p v-if="form.errors.status" class="text-sm text-red-500">{{ form.errors.status }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label>Fasilitas Kamar</Label>
                        <div v-if="facilities.length > 0" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <div v-for="facility in facilities" :key="facility.id" class="flex items-center space-x-2">
                                <Checkbox 
                                    :id="`r-facility-${facility.id}`" 
                                    :value="facility.id" 
                                    :modelValue="form.facilities.includes(facility.id)"
                                    @update:modelValue="(checked) => {
                                        if (checked) form.facilities.push(facility.id);
                                        else form.facilities = form.facilities.filter(id => id !== facility.id);
                                    }"
                                />
                                <Label :for="`r-facility-${facility.id}`" class="text-sm font-normal cursor-pointer flex items-center gap-1">
                                    <span v-html="facility.icon"></span>
                                    {{ facility.name }}
                                </Label>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button type="button" variant="outline" @click="closeModal">Batal</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ isEditing ? 'Simpan Perubahan' : 'Tambah Kamar' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </div>
</template>
