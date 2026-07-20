<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { MapPin, Loader2, Check, ChevronsUpDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import MapPicker from '@/Components/MapPicker.vue';
import { useToast } from '@/components/ui/toast/use-toast';
import axios from 'axios';

const props = defineProps({
    kos: Object,
    facilities: Array,
    isLocked: Boolean
});

const { toast } = useToast();

const sourceData = computed(() => {
    // If there's a pending revision, we might want to show it or at least indicate it. 
    // For simplicity, we just bind to the current valid data.
    return props.kos;
});

const form = useForm({
    name: sourceData.value.name,
    description: sourceData.value.description,
    address: sourceData.value.address,
    city: sourceData.value.city || '',
    district: sourceData.value.district || '',
    subdistrict: sourceData.value.subdistrict || '',
    latitude: sourceData.value.latitude || null,
    longitude: sourceData.value.longitude || null,
    public_contact_name: sourceData.value.public_contact_name || '',
    public_contact_whatsapp_number: sourceData.value.public_contact_whatsapp_number || '',
    payment_instructions: sourceData.value.payment_instructions || '',
    payment_proof_required: Boolean(sourceData.value.payment_proof_required),
    facilities: sourceData.value.facilities ? sourceData.value.facilities.map(f => f.id) : []
});

const submit = () => {
    form.put(route('owner.kos.update', props.kos.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast({
                title: "Berhasil",
                description: "Data kos berhasil diperbarui.",
            });
        }
    });
};

// --- Region Data (same as Create Form) ---
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

// Load cities on mount + pre-populate cascade for existing data
onMounted(async () => {
    await loadCities();

    // Pre-load districts if city already set
    if (form.city) {
        const cityObj = cities.value.find(c => c.name === form.city);
        if (cityObj) {
            try {
                const { data } = await axios.get('/api/regions/districts?city_code=' + cityObj.code);
                districts.value = data;
            } catch (e) { console.error(e); }
        }
    }

    // Pre-load subdistricts if district already set
    if (form.district) {
        const distObj = districts.value.find(d => d.name === form.district);
        if (distObj) {
            try {
                const { data } = await axios.get('/api/regions/villages?district_code=' + distObj.code);
                subdistricts.value = data;
            } catch (e) { console.error(e); }
        }
    }
});

// --- Reverse Geocoding (same as Create Form) ---
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
    <div class="space-y-8">
        <div v-if="isLocked" class="bg-orange-50 border border-orange-200 text-orange-800 p-4 rounded-lg flex items-start mb-6">
            <div class="mr-3 mt-0.5 flex-shrink-0">
                <Loader2 class="w-5 h-5 animate-spin" />
            </div>
            <div>
                <h4 class="font-semibold">Data Sedang Ditinjau</h4>
                <p class="text-sm mt-1">Anda tidak dapat mengubah data properti selama proses peninjauan oleh Super Admin berlangsung.</p>
            </div>
        </div>

        <fieldset :disabled="isLocked" :class="{ 'opacity-60': isLocked }">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Informasi Dasar -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold border-b pb-2">Informasi Dasar</h3>
                    
                    <div class="space-y-2">
                        <Label for="name">Nama Kos <span class="text-red-500">*</span></Label>
                        <Input id="name" v-model="form.name" required />
                        <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="description">Deskripsi <span class="text-red-500">*</span></Label>
                        <Textarea id="description" v-model="form.description" rows="4" required />
                        <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="public_contact_name">Nama Kontak Publik</Label>
                            <Input id="public_contact_name" v-model="form.public_contact_name" />
                            <p v-if="form.errors.public_contact_name" class="text-sm text-red-500">{{ form.errors.public_contact_name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="public_contact_whatsapp_number">Nomor WhatsApp Publik</Label>
                            <Input id="public_contact_whatsapp_number" v-model="form.public_contact_whatsapp_number" />
                            <p v-if="form.errors.public_contact_whatsapp_number" class="text-sm text-red-500">{{ form.errors.public_contact_whatsapp_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Lokasi -->
                <div class="space-y-4 pt-4">
                    <h3 class="text-lg font-semibold border-b pb-2">Lokasi</h3>
                    
                    <div class="mb-4">
                        <Label class="mb-2 block">Titik Lokasi Peta</Label>
                        <div class="h-[400px] border rounded-md overflow-hidden relative">
                            <MapPicker 
                                :modelValue="{ lat: form.latitude || -6.200000, lng: form.longitude || 106.816666 }" 
                                @update:modelValue="handleLocationSelected" 
                            />
                        </div>
                        <p v-if="form.latitude && form.longitude" class="text-xs text-green-600 mt-2 flex items-center">
                            <MapPin class="w-3 h-3 mr-1" /> Koordinat tersimpan: {{ form.latitude }}, {{ form.longitude }}
                        </p>
                        <p v-if="isGeocoding" class="text-xs text-blue-600 mt-1 flex items-center">
                            <Loader2 class="w-3 h-3 mr-1 animate-spin" /> Mendeteksi alamat wilayah...
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="address">Alamat Lengkap <span class="text-red-500">*</span></Label>
                        <Textarea id="address" v-model="form.address" rows="2" required />
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
                </div>

                <!-- Fasilitas Umum -->
                <div class="space-y-4 pt-4">
                    <h3 class="text-lg font-semibold border-b pb-2">Fasilitas Kos Umum</h3>
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
                                <span v-html="facility.icon"></span>
                                {{ facility.name }}
                            </Label>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500 italic">Belum ada master data fasilitas kos.</div>
                </div>

                <!-- Pengaturan Pembayaran -->
                <div class="space-y-4 pt-4">
                    <h3 class="text-lg font-semibold border-b pb-2">Pengaturan Pembayaran</h3>
                    <div class="space-y-2">
                        <Label for="payment_instructions">Instruksi Pembayaran (Transfer Bank, dll)</Label>
                        <Textarea id="payment_instructions" v-model="form.payment_instructions" rows="3" />
                        <p v-if="form.errors.payment_instructions" class="text-sm text-red-500">{{ form.errors.payment_instructions }}</p>
                    </div>
                    
                    <div class="flex items-center space-x-2 mt-4">
                        <Checkbox id="payment_proof_required" :modelValue="form.payment_proof_required" @update:modelValue="v => form.payment_proof_required = v" />
                        <Label for="payment_proof_required" class="font-normal cursor-pointer">Wajib unggah bukti transfer/pembayaran</Label>
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t mt-8">
                    <Button type="submit" :disabled="form.processing || isLocked">
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>Simpan Perubahan</span>
                    </Button>
                </div>
            </form>
        </fieldset>
    </div>
</template>
