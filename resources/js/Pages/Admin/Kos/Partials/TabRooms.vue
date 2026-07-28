<script setup>
import { toast } from 'vue-sonner';
import { ref, onMounted, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogScrollContent } from '@/components/ui/dialog';
import { Plus, Edit, Trash2, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    kos: Object,
    facilities: Array,
    isLocked: Boolean
});

const isEditing = ref(false);
const isBulk = ref(false);
const editingRoomId = ref(null);
const showModal = ref(false);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const editRoomId = params.get('edit_room');
    if (editRoomId) {
        const room = props.kos.rooms.find(r => r.id == editRoomId);
        if (room && !props.isLocked) {
            openEditModal(room);
        }
    }
});

// Table Filters and Pagination
const searchQuery = ref('');
const statusFilter = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

const filteredRooms = computed(() => {
    let result = props.kos.rooms || [];
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(r => 
            (r.name && r.name.toLowerCase().includes(query)) || 
            (r.room_number && r.room_number.toLowerCase().includes(query))
        );
    }
    
    if (statusFilter.value) {
        result = result.filter(r => r.status === statusFilter.value);
    }
    
    return result;
});

const totalPages = computed(() => Math.ceil(filteredRooms.value.length / itemsPerPage));

const paginatedRooms = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredRooms.value.slice(start, end);
});

watch([searchQuery, statusFilter], () => {
    currentPage.value = 1;
});

const form = useForm({
    name: '',
    room_number: '',
    bulk_prefix: 'Kamar',
    bulk_start: 1,
    bulk_count: 5,
    description: '',
    price: '',
    price_period: 'bulanan',
    capacity: 1,
    status: 'tersedia',
    facilities: []
});

const resetForm = () => {
    isEditing.value = false;
    isBulk.value = false;
    editingRoomId.value = null;
    editingRoomId.value = null;
    form.reset();
    form.clearErrors();
};

const openAddModal = () => {
    resetForm();
    showModal.value = true;
};

const openBulkModal = () => {
    resetForm();
    isBulk.value = true;
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
        form.put(route('admin.kos.rooms.update', { kos: props.kos.id, room: editingRoomId.value }), {
            preserveScroll: true,
            onSuccess: () => {
                // Notifikasi sukses ditangani oleh layout global (flash.success)
                closeModal();
            },
            onError: () => {
                toast.error('Periksa kembali data kamar Anda.');
            }
        });
    } else if (isBulk.value) {
        form.post(route('admin.kos.rooms.bulk', props.kos.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
            onError: () => {
                toast.error('Periksa kembali data kamar Anda.');
            }
        });
    } else {
        form.post(route('admin.kos.rooms.store', props.kos.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
            onError: () => {
                toast.error('Periksa kembali data kamar Anda.');
            }
        });
    }
};

const confirmingRoomDeletion = ref(false);
const roomToDelete = ref(null);
const isDeleting = ref(false);

const deleteRoom = (roomId) => {
    roomToDelete.value = roomId;
    confirmingRoomDeletion.value = true;
};

const closeDeleteModal = () => {
    confirmingRoomDeletion.value = false;
    setTimeout(() => {
        roomToDelete.value = null;
    }, 300);
};

const confirmDeleteRoom = () => {
    if (!roomToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('admin.kos.rooms.destroy', { kos: props.kos.id, room: roomToDelete.value }), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
        onError: (err) => {
            if (err.error) {
                toast.error(err.error);
            }
            closeDeleteModal();
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(price);
};

const formattedPrice = computed({
    get: () => {
        if (form.price === '' || form.price === null || form.price === undefined) return '';
        return form.price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    },
    set: (newValue) => {
        // Ensure newValue is a string before replacing
        let val = String(newValue).replace(/\D/g, '');
        form.price = val ? parseInt(val, 10) : '';
    }
});
</script>

<template>
    <div class="space-y-6">
        <div v-if="isLocked" class="bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-900 text-orange-800 dark:text-orange-400 p-4 rounded-lg flex items-start mb-6">
            <div class="mr-3 mt-0.5 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            </div>
            <div>
                <h4 class="font-semibold">Data Sedang Ditinjau</h4>
                <p class="text-sm mt-1">Anda tidak dapat menambah, mengubah, atau menghapus tipe kamar selama proses peninjauan oleh Super Admin berlangsung.</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 border-b dark:border-slate-800 pb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-200">Tipe Kamar</h3>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Kelola tipe-tipe kamar yang tersedia di kos ini beserta harga dan fasilitasnya.</p>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <Button v-if="!isLocked" @click="openBulkModal" variant="outline" class="w-full sm:w-auto border-teal-500 text-teal-600 hover:bg-teal-50">
                    <Plus class="w-4 h-4 mr-2" /> Buat Massal
                </Button>
                <Button v-if="!isLocked" @click="openAddModal" class="w-full sm:w-auto">
                    <Plus class="w-4 h-4 mr-2" /> Kamar Baru
                </Button>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex flex-col sm:flex-row gap-3 w-full flex-1">
                <Input v-model="searchQuery" placeholder="Cari nomor atau nama kamar..." class="max-w-xs w-full" />
                <select v-model="statusFilter" class="flex h-10 w-full sm:w-[180px] items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <option value="">Semua Status</option>
                    <option value="tersedia">Tersedia</option>
                    <option value="disewa">Disewa</option>
                    <option value="penuh">Penuh</option>
                    <option value="dalam_perbaikan">Dalam Perbaikan</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="border dark:border-slate-800 rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
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
                    <TableRow v-if="paginatedRooms.length === 0">
                        <TableCell colspan="5" class="text-center h-24 text-gray-500 dark:text-slate-400">
                            {{ kos.rooms && kos.rooms.length > 0 ? 'Tidak ada kamar yang sesuai dengan filter pencarian.' : 'Belum ada kamar.' }}
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="room in paginatedRooms" :key="room.id">
                        <TableCell>
                            <div class="font-semibold">{{ room.room_number }}</div>
                            <div class="text-sm text-gray-500 dark:text-slate-400">{{ room.name }}</div>
                        </TableCell>
                        <TableCell>
                            {{ formatPrice(room.price) }} <span class="text-xs text-gray-500 dark:text-slate-400">/ {{ room.price_period }}</span>
                        </TableCell>
                        <TableCell>{{ room.capacity }} orang</TableCell>
                        <TableCell>
                            <span class="px-2 py-1 text-xs rounded-full border" 
                                  :class="{
                                      'bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-900': room.status === 'tersedia',
                                      'bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-900': room.status === 'disewa',
                                      'bg-red-50 dark:bg-red-950/30 text-red-700 dark:text-red-400 border-red-200 dark:border-red-900': room.status === 'penuh',
                                      'bg-gray-50 dark:bg-slate-800/50 text-gray-700 dark:text-slate-400 border-gray-200 dark:border-slate-800': room.status === 'dalam_perbaikan'
                                  }">
                                {{ room.status }}
                            </span>
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <Button variant="outline" size="sm" @click="openEditModal(room)" :disabled="isLocked">
                                    <Edit class="w-4 h-4 mr-1" /> Edit
                                </Button>
                                <Button variant="outline" size="sm" class="text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/30" @click="deleteRoom(room.id)" :disabled="isLocked">
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            </div>
        </div>

        <!-- Pagination Controls -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-2" v-if="totalPages > 1">
            <div class="text-sm text-gray-500 dark:text-slate-400 text-center sm:text-left w-full sm:w-auto">
                Menampilkan {{ (currentPage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPage * itemsPerPage, filteredRooms.length) }} dari {{ filteredRooms.length }} kamar
            </div>
            <div class="flex flex-wrap justify-center gap-1">
                <Button variant="outline" size="sm" :disabled="currentPage === 1" @click="currentPage--">Sebelumnya</Button>
                <div class="hidden sm:flex items-center gap-1 px-2">
                    <span v-for="p in totalPages" :key="p" class="w-8 h-8 flex items-center justify-center rounded-md cursor-pointer text-sm transition-colors" :class="currentPage === p ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'hover:bg-slate-100 dark:hover:bg-slate-800'" @click="currentPage = p">
                        {{ p }}
                    </span>
                </div>
                <Button variant="outline" size="sm" :disabled="currentPage === totalPages" @click="currentPage++">Selanjutnya</Button>
            </div>
        </div>

        <!-- Modal Form Add/Edit -->
        <Dialog :open="showModal" @update:open="(val) => { if (!val) closeModal(); }">
            <DialogScrollContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Kamar' : (isBulk ? 'Buat Banyak Kamar Sekaligus' : 'Tambah Kamar Baru') }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Perbarui informasi kamar yang sudah ada.' : (isBulk ? 'Sistem akan membuat beberapa kamar sekaligus dengan spesifikasi yang sama.' : 'Masukkan informasi kamar baru untuk properti Anda.') }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4 py-2">
                    <div class="grid grid-cols-1 gap-4" :class="{'md:grid-cols-2': !isBulk}">
                        <div class="space-y-2">
                            <Label for="r_name">Tipe Kamar (Label) <span class="text-red-500">*</span></Label>
                            <Input id="r_name" v-model="form.name" placeholder="Misal: Kamar Standar / Kamar AC" required />
                            <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>
                        <div v-if="!isBulk" class="space-y-2">
                            <Label for="r_room_number">Nomor Kamar <span class="text-red-500">*</span></Label>
                            <Input id="r_room_number" v-model="form.room_number" placeholder="Misal: A1, B2" :required="!isBulk" />
                            <p v-if="form.errors.room_number" class="text-sm text-red-500">{{ form.errors.room_number }}</p>
                        </div>
                    </div>

                    <div v-if="isBulk" class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-teal-50/50 p-4 rounded-xl border border-teal-100">
                        <div class="space-y-2">
                            <Label for="r_bulk_prefix">Awalan Nama Kamar</Label>
                            <Input id="r_bulk_prefix" v-model="form.bulk_prefix" placeholder="Misal: Kamar" required />
                        </div>
                        <div class="space-y-2">
                            <Label for="r_bulk_start">Mulai dari Angka</Label>
                            <Input id="r_bulk_start" type="number" min="1" v-model="form.bulk_start" required />
                        </div>
                        <div class="space-y-2">
                            <Label for="r_bulk_count">Jumlah Kamar</Label>
                            <Input id="r_bulk_count" type="number" min="1" max="50" v-model="form.bulk_count" required />
                        </div>
                        <p class="text-xs text-teal-700 md:col-span-3">Preview: {{ form.bulk_prefix }}{{ form.bulk_start }}, {{ form.bulk_prefix }}{{ parseInt(form.bulk_start) + 1 }}, dst... sampai {{ form.bulk_count }} buah.</p>
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
                                <span class="absolute left-3 top-2 text-gray-500 dark:text-slate-400">Rp</span>
                                <Input id="r_price" type="text" v-model="formattedPrice" class="pl-10" required />
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

                    <DialogFooter class="pt-4 flex flex-col sm:flex-row gap-2">
                        <Button type="button" variant="outline" @click="closeModal" class="w-full sm:w-auto">Batal</Button>
                        <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
                            {{ isEditing ? 'Simpan Perubahan' : (isBulk ? 'Generate Kamar' : 'Tambah Kamar') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>

        <!-- Modal Dialog Hapus Kamar -->
        <Dialog :open="confirmingRoomDeletion" @update:open="val => { if(!val) closeDeleteModal(); }">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <div class="flex items-center gap-4 mb-2 text-destructive">
                        <div class="p-3 bg-destructive/10 rounded-full shrink-0">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <DialogTitle>Hapus Kamar?</DialogTitle>
                    </div>
                </DialogHeader>
                
                <DialogDescription class="text-sm">
                    Apakah Anda yakin ingin menghapus kamar ini? Data yang dihapus tidak dapat dikembalikan. Kamar tidak bisa dihapus jika masih ada penyewa aktif.
                </DialogDescription>

                <DialogFooter class="mt-6 flex flex-col sm:flex-row justify-end gap-2">
                    <Button variant="outline" @click="closeDeleteModal" :disabled="isDeleting" class="w-full sm:w-auto">Batal</Button>
                    <Button variant="destructive" @click="confirmDeleteRoom" :disabled="isDeleting" class="w-full sm:w-auto">
                        <svg v-if="isDeleting" class="w-4 h-4 mr-2 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
