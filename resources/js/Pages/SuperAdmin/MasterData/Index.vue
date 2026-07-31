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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Plus, Edit2, Trash2, AlertTriangle, X } from 'lucide-vue-next';
import StatusBadge from '@/components/StatusBadge.vue';

const props = defineProps({
    facilities: Object,
    rules: Object,
    filters: Object,
    activeTab: String,
});

// Using local state to manage active tab without changing URL immediately, 
// but we might want to sync with URL query
const currentTab = ref(props.activeTab || 'facilities');

const handleTabChange = (val) => {
    currentTab.value = val;
    router.get(route('superadmin.master-data.index'), { ...props.filters, tab: val }, { preserveState: true, replace: true });
};

// --- FACILITY LOGIC ---
const isFacilityModalOpen = ref(false);
const isFacilityEditing = ref(false);
const facilityEditingId = ref(null);

const facilityForm = useForm({
    name: '',
    type: 'kos',
    status: 'aktif',
});

const openFacilityCreateModal = () => {
    isFacilityEditing.value = false;
    facilityEditingId.value = null;
    facilityForm.reset();
    facilityForm.clearErrors();
    isFacilityModalOpen.value = true;
};

const openFacilityEditModal = (facility) => {
    isFacilityEditing.value = true;
    facilityEditingId.value = facility.id;
    facilityForm.name = facility.name;
    facilityForm.type = facility.type;
    facilityForm.status = facility.status;
    facilityForm.clearErrors();
    isFacilityModalOpen.value = true;
};

const submitFacility = () => {
    if (isFacilityEditing.value) {
        facilityForm.put(route('superadmin.facilities.update', facilityEditingId.value), {
            onSuccess: () => {
                isFacilityModalOpen.value = false;
            },
        });
    } else {
        facilityForm.post(route('superadmin.facilities.store'), {
            onSuccess: () => {
                isFacilityModalOpen.value = false;
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

const closeFacilityDeleteModal = () => {
    confirmingFacilityDeletion.value = false;
    setTimeout(() => {
        facilityToDelete.value = null;
    }, 300);
};

const deleteFacility = () => {
    if (!facilityToDelete.value) return;
    
    router.delete(route('superadmin.facilities.destroy', facilityToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeFacilityDeleteModal(),
    });
};

const handleFacilityFilter = (e) => {
    router.get(route('superadmin.master-data.index'), { ...props.filters, type: e.target.value, tab: 'facilities' }, { preserveState: true });
};


// --- RULE LOGIC ---
const isRuleModalOpen = ref(false);
const isRuleEditing = ref(false);
const ruleEditingId = ref(null);

const ruleForm = useForm({
    name: '',
    is_positive: true,
});

const openRuleCreateModal = () => {
    isRuleEditing.value = false;
    ruleEditingId.value = null;
    ruleForm.reset();
    ruleForm.clearErrors();
    isRuleModalOpen.value = true;
};

const openRuleEditModal = (rule) => {
    isRuleEditing.value = true;
    ruleEditingId.value = rule.id;
    ruleForm.name = rule.name;
    ruleForm.is_positive = !!rule.is_positive;
    ruleForm.clearErrors();
    isRuleModalOpen.value = true;
};

const submitRule = () => {
    if (isRuleEditing.value) {
        ruleForm.put(route('superadmin.rules.update', ruleEditingId.value), {
            onSuccess: () => {
                isRuleModalOpen.value = false;
            },
        });
    } else {
        ruleForm.post(route('superadmin.rules.store'), {
            onSuccess: () => {
                isRuleModalOpen.value = false;
            },
        });
    }
};

const confirmingRuleDeletion = ref(false);
const ruleToDelete = ref(null);

const confirmRuleDeletion = (rule) => {
    ruleToDelete.value = rule;
    confirmingRuleDeletion.value = true;
};

const closeRuleDeleteModal = () => {
    confirmingRuleDeletion.value = false;
    setTimeout(() => {
        ruleToDelete.value = null;
    }, 300);
};

const deleteRule = () => {
    if (!ruleToDelete.value) return;
    
    router.delete(route('superadmin.rules.destroy', ruleToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeRuleDeleteModal(),
    });
};

</script>

<template>
    <AppLayout>
        <Head title="Master Data" />

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Master Data</h2>
                <p class="text-gray-500 dark:text-slate-400 mt-1">Kelola data master fasilitas dan peraturan kos.</p>
            </div>
            <Button @click="currentTab === 'facilities' ? openFacilityCreateModal() : openRuleCreateModal()">
                <Plus class="w-4 h-4 mr-2" /> 
                Tambah {{ currentTab === 'facilities' ? 'Fasilitas' : 'Peraturan' }}
            </Button>
        </div>

        <Tabs :model-value="currentTab" @update:model-value="handleTabChange" class="w-full">
            <TabsList class="grid w-full max-w-[400px] grid-cols-2 mb-6 bg-slate-100 dark:bg-slate-800">
                <TabsTrigger value="facilities" class="data-[state=active]:bg-white dark:data-[state=active]:bg-slate-900 dark:text-slate-300">Fasilitas Kos & Kamar</TabsTrigger>
                <TabsTrigger value="rules" class="data-[state=active]:bg-white dark:data-[state=active]:bg-slate-900 dark:text-slate-300">Peraturan Kos</TabsTrigger>
            </TabsList>

            <!-- TAB FASILITAS -->
            <TabsContent value="facilities" class="mt-0 focus-visible:outline-none focus-visible:ring-0">
                <Card class="border-0 dark:bg-slate-900 shadow-sm">
                    <CardHeader class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b dark:border-slate-800">
                        <CardTitle class="dark:text-white">Daftar Fasilitas</CardTitle>
                        <div>
                            <Select :model-value="filters.type || 'all'" @update:modelValue="(val) => handleFacilityFilter({ target: { value: val } })">
                                <SelectTrigger class="w-[150px] dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200">
                                    <SelectValue placeholder="Semua Tipe" />
                                </SelectTrigger>
                                <SelectContent class="dark:bg-slate-800 dark:border-slate-700">
                                    <SelectGroup>
                                        <SelectItem value="all" class="dark:text-slate-200 dark:focus:bg-slate-700">Semua Tipe</SelectItem>
                                        <SelectItem value="kos" class="dark:text-slate-200 dark:focus:bg-slate-700">Kos</SelectItem>
                                        <SelectItem value="kamar" class="dark:text-slate-200 dark:focus:bg-slate-700">Kamar</SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0 border-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 dark:text-slate-400 uppercase bg-gray-50 dark:bg-slate-800/50 border-b dark:border-slate-800">
                                    <tr>
                                        <th class="px-4 py-3">Nama Fasilitas</th>
                                        <th class="px-4 py-3">Tipe</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="facility in facilities.data" :key="facility.id" class="border-b dark:border-slate-800 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-slate-200">{{ facility.name }}</td>
                                        <td class="px-4 py-3 capitalize dark:text-slate-400">{{ facility.type }}</td>
                                        <td class="px-4 py-3">
                                            <StatusBadge :status="facility.status" />
                                        </td>
                                        <td class="px-4 py-3 text-right flex justify-end gap-2">
                                            <Button variant="outline" size="sm" @click="openFacilityEditModal(facility)" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                                <Edit2 class="w-4 h-4" />
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="confirmFacilityDeletion(facility)">
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                    <tr v-if="facilities.data.length === 0">
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-slate-500">Tidak ada data fasilitas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
        
                        <!-- Pagination -->
                        <div v-if="facilities.links && facilities.links.length > 3" class="p-4 flex gap-1 border-t dark:border-slate-800 bg-gray-50 dark:bg-slate-900 rounded-b-lg">
                            <template v-for="(link, k) in facilities.links" :key="k">
                                <div v-if="link.url === null" class="px-3 py-1 text-sm border dark:border-slate-700 rounded text-gray-400 dark:text-slate-500 bg-gray-50 dark:bg-slate-800" v-html="link.label" />
                                <button v-else @click="router.get(link.url, { ...filters, tab: 'facilities' }, {preserveState: true})" class="px-3 py-1 text-sm border rounded transition-colors" :class="link.active ? 'bg-teal-600 text-white border-teal-600 cursor-default' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700'" :disabled="link.active" v-html="link.label" />
                            </template>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- TAB PERATURAN -->
            <TabsContent value="rules" class="mt-0 focus-visible:outline-none focus-visible:ring-0">
                <Card class="border-0 dark:bg-slate-900 shadow-sm">
                    <CardHeader class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b dark:border-slate-800">
                        <CardTitle class="dark:text-white">Daftar Peraturan</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0 border-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 dark:text-slate-400 uppercase bg-gray-50 dark:bg-slate-800/50 border-b dark:border-slate-800">
                                    <tr>
                                        <th class="px-4 py-3">Peraturan</th>
                                        <th class="px-4 py-3">Sifat</th>
                                        <th class="px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="rule in rules.data" :key="rule.id" class="border-b dark:border-slate-800 dark:hover:bg-slate-800/30 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-slate-200">
                                            {{ rule.name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span v-if="rule.is_positive" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400">
                                                Boleh
                                            </span>
                                            <span v-else class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                Dilarang
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right flex justify-end gap-2">
                                            <Button variant="outline" size="sm" @click="openRuleEditModal(rule)" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                                <Edit2 class="w-4 h-4" />
                                            </Button>
                                            <Button variant="destructive" size="sm" @click="confirmRuleDeletion(rule)">
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                    <tr v-if="rules.data.length === 0">
                                        <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-slate-500">Tidak ada data peraturan.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
        
                        <!-- Pagination -->
                        <div v-if="rules.links && rules.links.length > 3" class="p-4 flex gap-1 border-t dark:border-slate-800 bg-gray-50 dark:bg-slate-900 rounded-b-lg">
                            <template v-for="(link, k) in rules.links" :key="k">
                                <div v-if="link.url === null" class="px-3 py-1 text-sm border dark:border-slate-700 rounded text-gray-400 dark:text-slate-500 bg-gray-50 dark:bg-slate-800" v-html="link.label" />
                                <button v-else @click="router.get(link.url, { ...filters, tab: 'rules' }, {preserveState: true})" class="px-3 py-1 text-sm border rounded transition-colors" :class="link.active ? 'bg-teal-600 text-white border-teal-600 cursor-default' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700'" :disabled="link.active" v-html="link.label" />
                            </template>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>

        <!-- Facility Form Modal -->
        <Dialog :open="isFacilityModalOpen" @update:open="val => isFacilityModalOpen = val">
            <DialogContent class="sm:max-w-[425px] dark:bg-slate-900 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="dark:text-white">{{ isFacilityEditing ? 'Edit Fasilitas' : 'Tambah Fasilitas Baru' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitFacility" class="space-y-4 mt-4">
                    <div class="space-y-2">
                        <Label for="f-name" class="dark:text-slate-300">Nama Fasilitas</Label>
                        <Input id="f-name" type="text" v-model="facilityForm.name" required class="dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500" />
                        <p v-if="facilityForm.errors.name" class="text-sm text-destructive dark:text-red-400">{{ facilityForm.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="f-type" class="dark:text-slate-300">Tipe</Label>
                        <Select id="f-type" v-model="facilityForm.type">
                            <SelectTrigger class="dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                                <SelectValue placeholder="Pilih Tipe" />
                            </SelectTrigger>
                            <SelectContent class="dark:bg-slate-800 dark:border-slate-700">
                                <SelectGroup>
                                    <SelectItem value="kos" class="dark:text-slate-200 dark:focus:bg-slate-700">Kos</SelectItem>
                                    <SelectItem value="kamar" class="dark:text-slate-200 dark:focus:bg-slate-700">Kamar</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <p v-if="facilityForm.errors.type" class="text-sm text-destructive dark:text-red-400">{{ facilityForm.errors.type }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="f-status" class="dark:text-slate-300">Status</Label>
                        <Select id="f-status" v-model="facilityForm.status">
                            <SelectTrigger class="dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                                <SelectValue placeholder="Pilih Status" />
                            </SelectTrigger>
                            <SelectContent class="dark:bg-slate-800 dark:border-slate-700">
                                <SelectGroup>
                                    <SelectItem value="aktif" class="dark:text-slate-200 dark:focus:bg-slate-700">Aktif</SelectItem>
                                    <SelectItem value="nonaktif" class="dark:text-slate-200 dark:focus:bg-slate-700">Nonaktif</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <p v-if="facilityForm.errors.status" class="text-sm text-destructive dark:text-red-400">{{ facilityForm.errors.status }}</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" @click="isFacilityModalOpen = false" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Button>
                        <Button type="submit" :disabled="facilityForm.processing">Simpan</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Rule Form Modal -->
        <Dialog :open="isRuleModalOpen" @update:open="val => isRuleModalOpen = val">
            <DialogContent class="sm:max-w-[425px] dark:bg-slate-900 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="dark:text-white">{{ isRuleEditing ? 'Edit Peraturan' : 'Tambah Peraturan Baru' }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submitRule" class="space-y-4 mt-4">
                    <div class="space-y-2">
                        <Label for="r-name" class="dark:text-slate-300">Nama Peraturan</Label>
                        <Input id="r-name" type="text" v-model="ruleForm.name" required placeholder="Misal: Dilarang bawa hewan peliharaan" class="dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500" />
                        <p v-if="ruleForm.errors.name" class="text-sm text-destructive dark:text-red-400">{{ ruleForm.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="r-is_positive" class="dark:text-slate-300">Sifat Aturan</Label>
                        <Select id="r-is_positive" :model-value="ruleForm.is_positive ? '1' : '0'" @update:model-value="(val) => ruleForm.is_positive = val === '1'">
                            <SelectTrigger class="dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                                <SelectValue placeholder="Pilih Sifat" />
                            </SelectTrigger>
                            <SelectContent class="dark:bg-slate-800 dark:border-slate-700">
                                <SelectGroup>
                                    <SelectItem value="1" class="dark:text-slate-200 dark:focus:bg-slate-700">Positif / Boleh</SelectItem>
                                    <SelectItem value="0" class="dark:text-slate-200 dark:focus:bg-slate-700">Negatif / Dilarang</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <p v-if="ruleForm.errors.is_positive" class="text-sm text-destructive dark:text-red-400">{{ ruleForm.errors.is_positive }}</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" @click="isRuleModalOpen = false" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Button>
                        <Button type="submit" :disabled="ruleForm.processing">Simpan</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Facility Delete Confirmation Dialog -->
        <Dialog :open="confirmingFacilityDeletion" @update:open="val => { if(!val) closeFacilityDeleteModal(); }">
            <DialogContent class="sm:max-w-[425px] dark:bg-slate-900 dark:border-slate-800">
                <DialogHeader>
                    <div class="flex items-center gap-4 mb-2 text-destructive dark:text-red-400">
                        <div class="p-3 bg-destructive/10 dark:bg-red-900/30 rounded-full shrink-0">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <DialogTitle>Hapus Fasilitas?</DialogTitle>
                    </div>
                </DialogHeader>
                <DialogDescription class="text-sm dark:text-slate-400">
                    Apakah Anda yakin ingin menghapus fasilitas <strong class="text-foreground dark:text-white">{{ facilityToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                </DialogDescription>
                <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                    <Button variant="outline" @click="closeFacilityDeleteModal" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Button>
                    <Button variant="destructive" @click="deleteFacility">Ya, Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Rule Delete Confirmation Dialog -->
        <Dialog :open="confirmingRuleDeletion" @update:open="val => { if(!val) closeRuleDeleteModal(); }">
            <DialogContent class="sm:max-w-[425px] dark:bg-slate-900 dark:border-slate-800">
                <DialogHeader>
                    <div class="flex items-center gap-4 mb-2 text-destructive dark:text-red-400">
                        <div class="p-3 bg-destructive/10 dark:bg-red-900/30 rounded-full shrink-0">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <DialogTitle>Hapus Peraturan?</DialogTitle>
                    </div>
                </DialogHeader>
                <DialogDescription class="text-sm dark:text-slate-400">
                    Apakah Anda yakin ingin menghapus peraturan <strong class="text-foreground dark:text-white">{{ ruleToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
                </DialogDescription>
                <DialogFooter class="mt-6 flex justify-end gap-3 sm:justify-end">
                    <Button variant="outline" @click="closeRuleDeleteModal" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Batal</Button>
                    <Button variant="destructive" @click="deleteRule">Ya, Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
