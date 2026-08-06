<script setup>
import { toast } from 'vue-sonner';
import { ref, onMounted, nextTick } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Deferred } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ChevronLeft, MapPin, Loader2, Check, ChevronsUpDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import MapPicker from '@/components/MapPicker.vue';
import axios from 'axios';

const props = defineProps({
    facilities: Array,
    rules: Array
});

const form = useForm({
    name: '',
    description: '',
    address: '',
    city: '',
    district: '',
    subdistrict: '',
    latitude: null,
    longitude: null,
    public_contact_name: '',
    public_contact_whatsapp_number: '',
    facilities: [],
    rules: []
});


const submit = () => {
    form.post(route('admin.kos.store'), {
        onSuccess: () => {
            toast.success('Data kos berhasil disimpan sebagai draft.');
        },
        onError: () => {
            toast.error('Periksa kembali data yang Anda masukkan.');
        }
    });
};

const cities = ref([]);
const districts = ref([]);
const subdistricts = ref([]);
const isGeocoding = ref(false);

const cityOpen = ref(false);
const districtOpen = ref(false);
const subdistrictOpen = ref(false);

const loadCities = async () => {
    try {
        const { data } = await axios.get('/api/regions/cities');
        cities.value = data;
    } catch (e) { console.error(e); }
};

const onCityChange = async () => {
    form.district = '';
    form.subdistrict = '';
    districts.value = [];
    subdistricts.value = [];
    if (!form.city) return;
    try {
        const cityObj = cities.value.find(c => c.name === form.city);
        if(cityObj) {
            const { data } = await axios.get('/api/regions/districts?city_code=' + cityObj.code);
            districts.value = data;
        }
    } catch (e) { console.error(e); }
};

const onDistrictChange = async () => {
    form.subdistrict = '';
    subdistricts.value = [];
    if (!form.district) return;
    try {
        const distObj = districts.value.find(d => d.name === form.district);
        if(distObj) {
            const { data } = await axios.get('/api/regions/villages?district_code=' + distObj.code);
            subdistricts.value = data;
        }
    } catch (e) { console.error(e); }
};

// Load cities on mount
onMounted(() => {
    loadCities();
});

const handleLocationSelected = async (location) => {
    form.latitude = location.lat;
    form.longitude = location.lng;
    
    isGeocoding.value = true;
    try {
        // Reverse geocoding via Nominatim
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${location.lat}&lon=${location.lng}&format=json`);
        const geocode = await res.json();
        
        if (geocode && geocode.display_name) {
            // Match with our DB using display_name
            const matchRes = await axios.post('/api/regions/match', {
                display_name: geocode.display_name
            });
            
            const matched = matchRes.data;
            if (matched.city) {
                form.city = matched.city.name;
                await onCityChange();
                await nextTick();
            }
            if (matched.district) {
                form.district = matched.district.name;
                await onDistrictChange();
                await nextTick();
            }
            if (matched.village) {
                form.subdistrict = matched.village.name;
            }
        }
    } catch (error) {
        console.error("Gagal mendeteksi lokasi", error);
    } finally {
        isGeocoding.value = false;
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Tambah Kos" />

        <Deferred :data="['facilities', 'rules']">
            <template #fallback>
                <div class="animate-pulse">
                    <div class="mb-6">
                        <div class="h-4 w-32 bg-slate-200 dark:bg-slate-800 rounded mb-4"></div>
                        <div class="h-8 w-48 bg-slate-200 dark:bg-slate-800 rounded mb-2"></div>
                        <div class="h-4 w-64 bg-slate-200 dark:bg-slate-800 rounded"></div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="h-[400px] bg-slate-200 dark:bg-slate-800 rounded-lg"></div>
                            <div class="h-[200px] bg-slate-200 dark:bg-slate-800 rounded-lg"></div>
                        </div>
                        <div class="space-y-6">
                            <div class="h-[300px] bg-slate-200 dark:bg-slate-800 rounded-lg"></div>
                        </div>
                    </div>
                </div>
            </template>

        <div class="mb-6">
            <Link :href="route('admin.kos.index')" class="text-sm text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white flex items-center mb-4 inline-flex transition-colors">
                <ChevronLeft class="w-4 h-4 mr-1" /> Kembali ke Daftar Kos
            </Link>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Tambah Properti Kos</h2>
            <p class="text-gray-500 dark:text-slate-400 mt-1">Masukkan informasi dasar properti kos Anda.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <Card>
                <CardHeader>
                    <CardTitle>Informasi Dasar</CardTitle>
                    <CardDescription>Nama dan deskripsi kos yang akan ditampilkan ke publik.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Nama Kos <span class="text-red-500">*</span></Label>
                        <Input id="name" v-model="form.name" placeholder="Misal: Kos Mawar Indah" required />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="description">Deskripsi <span class="text-red-500">*</span></Label>
                        <Textarea id="description" v-model="form.description" rows="4" placeholder="Ceritakan tentang kos Anda, fasilitas unggulan, atau aturan umum. Tip: Sebutkan landmark terdekat (mis. 'dekat RS Harapan Kita', '5 menit ke Stasiun Sudirman', 'dekat Kampus UI') agar mudah ditemukan calon penyewa via WhatsApp bot." required />
                        <p class="text-xs text-gray-500 dark:text-slate-400">💡 Tip: Sebutkan rumah sakit, kampus, stasiun, atau tempat terkenal terdekat. Bot WhatsApp kami menggunakan info ini untuk membantu penyewa mencari kos berdasarkan lokasi.</p>
                        <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="public_contact_name">Nama Kontak Publik</Label>
                            <Input id="public_contact_name" v-model="form.public_contact_name" placeholder="Misal: Bp. Budi (Pengelola)" />
                            <p v-if="form.errors.public_contact_name" class="text-sm text-red-500">{{ form.errors.public_contact_name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="public_contact_whatsapp_number">Nomor WhatsApp Publik</Label>
                            <Input id="public_contact_whatsapp_number" v-model="form.public_contact_whatsapp_number" placeholder="Misal: 081234567890" />
                            <p class="text-xs text-gray-500 dark:text-slate-400">Nomor yang bisa dihubungi oleh calon penyewa.</p>
                            <p v-if="form.errors.public_contact_whatsapp_number" class="text-sm text-red-500">{{ form.errors.public_contact_whatsapp_number }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Lokasi</CardTitle>
                    <CardDescription>Tentukan lokasi akurat kos Anda di peta dan isi detail wilayah.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="mb-4">
                        <Label class="mb-2 block">Titik Lokasi Peta</Label>
                        <div class="h-[400px] border dark:border-slate-700 rounded-md overflow-hidden relative">
                            <MapPicker @update:modelValue="handleLocationSelected" />
                        </div>
                        <p v-if="form.latitude && form.longitude" class="text-xs text-green-600 dark:text-green-400 mt-2 flex items-center">
                            <MapPin class="w-3 h-3 mr-1" /> Koordinat tersimpan: {{ form.latitude.toFixed(6) }}, {{ form.longitude.toFixed(6) }}
                        </p>
                        <p v-if="isGeocoding" class="text-xs text-blue-600 dark:text-blue-400 mt-1 flex items-center">
                            <Loader2 class="w-3 h-3 mr-1 animate-spin" /> Mendeteksi alamat wilayah...
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="address">Alamat Lengkap <span class="text-red-500">*</span></Label>
                        <Textarea id="address" v-model="form.address" rows="2" placeholder="Jalan, RT/RW, Nomor Rumah..." required />
                        <p v-if="form.errors.address" class="text-sm text-red-500">{{ form.errors.address }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2 flex flex-col">
                            <Label for="city">Kota/Kabupaten</Label>
                            <Popover v-model:open="cityOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        id="city"
                                        variant="outline"
                                        role="combobox"
                                        :aria-expanded="cityOpen"
                                        class="w-full justify-between font-normal"
                                        :class="{ 'border-red-500': form.errors.city, 'text-gray-500': !form.city }"
                                    >
                                        {{ form.city || 'Pilih Kota/Kabupaten' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-full p-0">
                                    <Command>
                                        <CommandInput placeholder="Cari kota..." />
                                        <CommandEmpty>Kota tidak ditemukan.</CommandEmpty>
                                        <CommandList>
                                            <CommandGroup>
                                                <CommandItem
                                                    v-for="c in cities"
                                                    :key="c.code"
                                                    :value="c.name"
                                                    @select="() => {
                                                        form.city = c.name;
                                                        cityOpen = false;
                                                        onCityChange();
                                                    }"
                                                >
                                                    <Check
                                                        :class="cn('mr-2 h-4 w-4', form.city === c.name ? 'opacity-100' : 'opacity-0')"
                                                    />
                                                    {{ c.name }}
                                                </CommandItem>
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <p v-if="form.errors.city" class="text-sm text-red-500">{{ form.errors.city }}</p>
                        </div>

                        <div class="space-y-2 flex flex-col">
                            <Label for="district">Kecamatan</Label>
                            <Popover v-model:open="districtOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        id="district"
                                        variant="outline"
                                        role="combobox"
                                        :aria-expanded="districtOpen"
                                        :disabled="!form.city || districts.length === 0"
                                        class="w-full justify-between font-normal"
                                        :class="{ 'border-red-500': form.errors.district, 'text-gray-500': !form.district }"
                                    >
                                        {{ form.district || 'Pilih Kecamatan' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-full p-0">
                                    <Command>
                                        <CommandInput placeholder="Cari kecamatan..." />
                                        <CommandEmpty>Kecamatan tidak ditemukan.</CommandEmpty>
                                        <CommandList>
                                            <CommandGroup>
                                                <CommandItem
                                                    v-for="d in districts"
                                                    :key="d.code"
                                                    :value="d.name"
                                                    @select="() => {
                                                        form.district = d.name;
                                                        districtOpen = false;
                                                        onDistrictChange();
                                                    }"
                                                >
                                                    <Check
                                                        :class="cn('mr-2 h-4 w-4', form.district === d.name ? 'opacity-100' : 'opacity-0')"
                                                    />
                                                    {{ d.name }}
                                                </CommandItem>
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <p v-if="form.errors.district" class="text-sm text-red-500">{{ form.errors.district }}</p>
                        </div>

                        <div class="space-y-2 flex flex-col">
                            <Label for="subdistrict">Kelurahan</Label>
                            <Popover v-model:open="subdistrictOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        id="subdistrict"
                                        variant="outline"
                                        role="combobox"
                                        :aria-expanded="subdistrictOpen"
                                        :disabled="!form.district || subdistricts.length === 0"
                                        class="w-full justify-between font-normal"
                                        :class="{ 'border-red-500': form.errors.subdistrict, 'text-gray-500': !form.subdistrict }"
                                    >
                                        {{ form.subdistrict || 'Pilih Kelurahan' }}
                                        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-full p-0">
                                    <Command>
                                        <CommandInput placeholder="Cari kelurahan..." />
                                        <CommandEmpty>Kelurahan tidak ditemukan.</CommandEmpty>
                                        <CommandList>
                                            <CommandGroup>
                                                <CommandItem
                                                    v-for="v in subdistricts"
                                                    :key="v.code"
                                                    :value="v.name"
                                                    @select="() => {
                                                        form.subdistrict = v.name;
                                                        subdistrictOpen = false;
                                                    }"
                                                >
                                                    <Check
                                                        :class="cn('mr-2 h-4 w-4', form.subdistrict === v.name ? 'opacity-100' : 'opacity-0')"
                                                    />
                                                    {{ v.name }}
                                                </CommandItem>
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <p v-if="form.errors.subdistrict" class="text-sm text-red-500">{{ form.errors.subdistrict }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Fasilitas Kos Umum</CardTitle>
                    <CardDescription>Pilih fasilitas yang tersedia untuk seluruh penghuni kos.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="facilities.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div v-for="facility in facilities" :key="facility.id" class="flex items-center space-x-2">
                            <Checkbox 
                                :id="`facility-${facility.id}`" 
                                :value="facility.id" 
                                :modelValue="form.facilities.includes(facility.id)"
                                @update:modelValue="(checked) => {
                                    if (checked) form.facilities.push(facility.id);
                                    else form.facilities = form.facilities.filter(id => id !== facility.id);
                                }"
                            />
                            <Label :for="`facility-${facility.id}`" class="text-sm font-normal cursor-pointer flex items-center gap-2">
                                {{ facility.name }}
                            </Label>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500 dark:text-slate-400 italic">Belum ada master data fasilitas kos.</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Peraturan & Tata Tertib</CardTitle>
                    <CardDescription>Pilih peraturan yang berlaku untuk kos Anda.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="rules && rules.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div v-for="rule in rules" :key="rule.id" class="flex items-center space-x-2">
                            <Checkbox 
                                :id="`rule-${rule.id}`" 
                                :value="rule.id" 
                                :modelValue="form.rules.includes(rule.id)"
                                @update:modelValue="(checked) => {
                                    if (checked) form.rules.push(rule.id);
                                    else form.rules = form.rules.filter(id => id !== rule.id);
                                }"
                            />
                            <Label :for="`rule-${rule.id}`" class="text-sm font-normal cursor-pointer flex items-center gap-2 leading-tight">
                                {{ rule.name }}
                            </Label>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500 dark:text-slate-400 italic">Belum ada master data peraturan kos.</div>
                </CardContent>
            </Card>



            <div class="flex justify-end gap-3">
                <Link :href="route('admin.kos.index')">
                    <Button type="button" variant="outline">Batal</Button>
                </Link>
                <Button type="submit" :disabled="form.processing">
                    <span v-if="form.processing">Menyimpan...</span>
                    <span v-else>Simpan sebagai Draft</span>
                </Button>
            </div>
        </form>
        </Deferred>
    </AppLayout>
</template>
