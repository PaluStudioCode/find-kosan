<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { MapPin, Phone, User, CheckCircle2, BedDouble } from 'lucide-vue-next';
import PhotoGallery from '@/Components/PhotoGallery.vue';
import KosMap from '@/Components/KosMap.vue';
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/use-toast';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

const props = defineProps({
    kos: Object,
});

const { toast } = useToast();

const formatRupiah = (amount) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
};

const bookingRoom = ref(null);
const form = useForm({
    start_date: '',
    occupant_count: 1
});

const openBookModal = (room) => {
    bookingRoom.value = room;
    form.start_date = '';
    form.occupant_count = 1;
};

const submitBooking = () => {
    form.post(route('tenant.tenancies.store', bookingRoom.value.id), {
        onSuccess: () => {
            bookingRoom.value = null;
            // Success is handled via redirect from controller
        },
        onError: (err) => {
            if (err.start_date) toast({ title: 'Gagal', description: err.start_date, variant: 'destructive' });
            if (err.occupant_count) toast({ title: 'Gagal', description: err.occupant_count, variant: 'destructive' });
        }
    });
};
</script>

<template>
    <PublicLayout>
        <Head :title="kos.name" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">{{ kos.name }}</h1>
                <p class="text-gray-500 mt-2 flex items-center">
                    <MapPin class="w-4 h-4 mr-1" /> {{ kos.address }}, {{ kos.subdistrict }}, {{ kos.district }}, {{ kos.city }}
                </p>
            </div>

            <!-- Photos -->
            <PhotoGallery :photos="kos.photos.map(p => ({ id: p.id, url: p.file_path, caption: p.caption }))" class="mb-8" />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <section>
                        <h2 class="text-xl font-semibold mb-4">Deskripsi</h2>
                        <div class="prose max-w-none text-gray-700 whitespace-pre-wrap">{{ kos.description }}</div>
                    </section>

                    <section v-if="kos.facilities.length > 0">
                        <h2 class="text-xl font-semibold mb-4">Fasilitas Kos</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div v-for="fac in kos.facilities" :key="fac.id" class="flex items-center text-gray-600">
                                <CheckCircle2 class="w-4 h-4 mr-2 text-primary" /> {{ fac.name }}
                            </div>
                        </div>
                    </section>
                    
                    <section v-if="kos.latitude && kos.longitude">
                        <h2 class="text-xl font-semibold mb-4">Peta Lokasi</h2>
                        <KosMap :items="[kos]" :center="{ lat: kos.latitude, lng: kos.longitude }" />
                    </section>

                    <section>
                        <h2 class="text-xl font-semibold mb-4">Daftar Kamar</h2>
                        <div v-if="kos.rooms.length > 0" class="space-y-4">
                            <Card v-for="room in kos.rooms" :key="room.id">
                                <div class="flex flex-col sm:flex-row">
                                    <div class="sm:w-1/3">
                                        <img v-if="room.photos && room.photos.length > 0" :src="room.photos[0].file_path" class="w-full h-full object-cover sm:rounded-l-lg" />
                                        <div v-else class="w-full h-48 sm:h-full bg-gray-200 flex items-center justify-center sm:rounded-l-lg">
                                            <BedDouble class="w-8 h-8 text-gray-400" />
                                        </div>
                                    </div>
                                    <div class="p-6 sm:w-2/3 flex flex-col justify-between">
                                        <div>
                                            <h3 class="text-lg font-bold">{{ room.name }}</h3>
                                            <p class="text-sm text-gray-500 mb-2">No. Kamar: {{ room.room_number }} • Kapasitas: {{ room.capacity }} orang</p>
                                            <div class="text-gray-700 text-sm mb-4 line-clamp-2">{{ room.description }}</div>
                                            
                                            <div class="flex flex-wrap gap-1 mb-4">
                                                <span v-for="fac in room.facilities" :key="fac.id" class="px-2 py-1 bg-gray-100 text-xs rounded-full text-gray-600">
                                                    {{ fac.name }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center justify-between mt-auto">
                                            <div class="text-lg font-bold text-primary">
                                                {{ formatRupiah(room.price) }}<span class="text-sm font-normal text-gray-500">/{{ room.price_period }}</span>
                                            </div>
                                            <template v-if="$page.props.auth.user && $page.props.auth.user.role === 'penyewa'">
                                                <Button size="sm" @click="openBookModal(room)" :disabled="room.status !== 'tersedia'">
                                                    {{ room.status === 'tersedia' ? 'Pesan Sekarang' : 'Penuh' }}
                                                </Button>
                                            </template>
                                            <template v-else-if="!$page.props.auth.user">
                                                <Link :href="route('login')">
                                                    <Button size="sm">Login untuk Pesan</Button>
                                                </Link>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </Card>
                        </div>
                        <div v-else class="text-center py-8 bg-gray-50 rounded-lg border">
                            <p class="text-gray-500">Belum ada kamar yang tersedia saat ini.</p>
                        </div>
                    </section>
                </div>

                <!-- Sidebar (Contact & Info) -->
                <div>
                    <Card class="sticky top-24">
                        <CardHeader>
                            <CardTitle>Informasi Pengelola</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                                    <User class="w-6 h-6 text-primary" />
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ kos.public_contact_name || kos.owner.name }}</div>
                                    <div class="text-sm text-gray-500">Pemilik / Pengelola</div>
                                </div>
                            </div>
                            
                            <a :href="`https://wa.me/${(kos.public_contact_whatsapp_number || kos.owner.whatsapp_number).replace(/[^0-9]/g, '')}`" target="_blank" class="flex items-center justify-center gap-2 w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded-md transition-colors font-medium">
                                <Phone class="w-4 h-4" /> Hubungi via WhatsApp
                            </a>
                            
                            <div v-if="!$page.props.auth.user" class="text-center mt-4 text-sm text-gray-500 border-t pt-4">
                                Ingin menyewa? <Link :href="route('login')" class="text-primary hover:underline">Login</Link> terlebih dahulu.
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Dialog Pesan Kamar -->
        <Dialog :open="!!bookingRoom" @update:open="(val) => { if(!val) bookingRoom = null }">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Pesan Kamar</DialogTitle>
                </DialogHeader>
                
                <form @submit.prevent="submitBooking" class="space-y-4">
                    <div v-if="bookingRoom">
                        <p class="font-medium">{{ bookingRoom.name }}</p>
                        <p class="text-sm text-gray-500 mb-4">{{ formatRupiah(bookingRoom.price) }} / {{ bookingRoom.price_period }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="start_date">Mulai Sewa (Tanggal)</Label>
                        <Input id="start_date" type="date" v-model="form.start_date" required />
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="occupant_count">Jumlah Penghuni</Label>
                        <Input id="occupant_count" type="number" min="1" :max="bookingRoom?.capacity || 1" v-model="form.occupant_count" required />
                        <p class="text-xs text-gray-500">Maksimal {{ bookingRoom?.capacity }} orang</p>
                    </div>

                    <DialogFooter class="mt-6">
                        <Button type="button" variant="ghost" @click="bookingRoom = null">Batal</Button>
                        <Button type="submit" :disabled="form.processing">Ajukan Sewa</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </PublicLayout>
</template>
