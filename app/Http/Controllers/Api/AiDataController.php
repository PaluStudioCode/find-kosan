<?php

namespace App\Http\Controllers\Api;

use App\Helpers\WhatsappNumber;
use App\Http\Controllers\Controller;
use App\Models\BoardingHouse;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiDataController extends Controller
{
    public function identifyUser(string $phone): JsonResponse
    {
        $normalized = WhatsappNumber::normalize($phone);

        if (! $normalized) {
            return response()->json(['role' => 'guest', 'user' => null]);
        }

        $user = User::where('whatsapp_number', $normalized)->first();

        if (! $user) {
            return response()->json(['role' => 'guest', 'user' => null]);
        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'whatsapp_number' => $user->whatsapp_number,
        ];

        if ($user->role === 'admin') {
            $data['kos_count'] = $user->boardingHouses()->where('status', 'dipublikasikan')->count();
        }

        if ($user->role === 'user') {
            $data['active_tenancy'] = $user->tenanciesAsTenant()->where('status', 'aktif')->exists();
        }

        return response()->json(['role' => $user->role, 'user' => $data]);
    }

    public function searchKos(Request $request): JsonResponse
    {
        $query = BoardingHouse::where('status', 'dipublikasikan');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('description', 'LIKE', "%{$keyword}%")
                    ->orWhere('address', 'LIKE', "%{$keyword}%");
            });
        }

        if ($request->filled('city')) {
            $query->where('city', 'LIKE', '%'.$request->input('city').'%');
        }

        if ($request->filled('district')) {
            $query->where('district', 'LIKE', '%'.$request->input('district').'%');
        }

        if ($request->filled('subdistrict')) {
            $query->where('subdistrict', 'LIKE', '%'.$request->input('subdistrict').'%');
        }

        if ($request->filled('max_price')) {
            $maxPrice = $request->input('max_price');
            $query->whereHas('rooms', function ($q) use ($maxPrice) {
                $q->where('status', 'tersedia')->where('price', '<=', $maxPrice);
            });
        }

        if ($request->filled('facility')) {
            $facility = $request->input('facility');
            $query->whereHas('facilities', function ($q) use ($facility) {
                $q->where('name', 'LIKE', "%{$facility}%");
            });
        }

        $results = $query->with([
            'rooms' => fn ($q) => $q->where('status', 'tersedia')->select('id', 'boarding_house_id', 'name', 'price', 'price_period', 'capacity'),
            'facilities:id,name',
        ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->limit(10)
            ->get(['id', 'name', 'address', 'city', 'district', 'subdistrict', 'description']);

        $data = $results->map(function ($kos) {
            $rooms = $kos->rooms;

            return [
                'id' => $kos->id,
                'name' => $kos->name,
                'address' => $kos->address,
                'city' => $kos->city,
                'district' => $kos->district,
                'subdistrict' => $kos->subdistrict,
                'description' => Str::limit($kos->description, 200),
                'price_range' => $rooms->count() > 0
                    ? ['min' => (float) $rooms->min('price'), 'max' => (float) $rooms->max('price')]
                    : null,
                'available_rooms' => $rooms->count(),
                'facilities' => $kos->facilities->pluck('name'),
                'rating' => $kos->reviews_avg_rating ? round($kos->reviews_avg_rating, 1) : null,
                'review_count' => $kos->reviews_count,
            ];
        });

        return response()->json(['results' => $data, 'count' => $data->count()]);
    }

    public function kosDetail(int $id): JsonResponse
    {
        $kos = BoardingHouse::where('status', 'dipublikasikan')
            ->with([
                'rooms' => fn ($q) => $q->select('id', 'boarding_house_id', 'name', 'room_number', 'description', 'price', 'price_period', 'capacity', 'status'),
                'rooms.facilities:id,name',
                'facilities:id,name',
                'rules:id,name,is_positive',
                'reviews' => fn ($q) => $q->with('user:id,name')->latest()->limit(5),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->find($id);

        if (! $kos) {
            return response()->json(['error' => 'Kos tidak ditemukan'], 404);
        }

        return response()->json([
            'id' => $kos->id,
            'name' => $kos->name,
            'description' => $kos->description,
            'address' => $kos->address,
            'city' => $kos->city,
            'district' => $kos->district,
            'subdistrict' => $kos->subdistrict,
            'public_contact_name' => $kos->public_contact_name,
            'public_contact_whatsapp_number' => $kos->public_contact_whatsapp_number,
            'facilities' => $kos->facilities->pluck('name'),
            'rules' => $kos->rules->map(fn ($r) => [
                'name' => $r->name,
                'type' => $r->is_positive ? 'diperbolehkan' : 'dilarang',
            ]),
            'rooms' => $kos->rooms->map(fn ($room) => [
                'id' => $room->id,
                'name' => $room->name,
                'room_number' => $room->room_number,
                'description' => $room->description,
                'price' => (float) $room->price,
                'price_period' => $room->price_period,
                'capacity' => $room->capacity,
                'status' => $room->status,
                'facilities' => $room->facilities->pluck('name'),
            ]),
            'rating' => $kos->reviews_avg_rating ? round($kos->reviews_avg_rating, 1) : null,
            'review_count' => $kos->reviews_count,
            'recent_reviews' => $kos->reviews->map(fn ($r) => [
                'user' => $r->user->name,
                'rating' => $r->rating,
                'comment' => $r->comment,
            ]),
        ]);
    }

    public function kosRooms(int $id): JsonResponse
    {
        $kos = BoardingHouse::where('status', 'dipublikasikan')->find($id);

        if (! $kos) {
            return response()->json(['error' => 'Kos tidak ditemukan'], 404);
        }

        $rooms = $kos->rooms()
            ->where('status', 'tersedia')
            ->with('facilities:id,name')
            ->get(['id', 'boarding_house_id', 'name', 'room_number', 'description', 'price', 'price_period', 'capacity']);

        return response()->json([
            'kos_name' => $kos->name,
            'available_rooms' => $rooms->map(fn ($room) => [
                'id' => $room->id,
                'name' => $room->name,
                'room_number' => $room->room_number,
                'description' => $room->description,
                'price' => (float) $room->price,
                'price_period' => $room->price_period,
                'capacity' => $room->capacity,
                'facilities' => $room->facilities->pluck('name'),
            ]),
            'count' => $rooms->count(),
        ]);
    }

    public function userTenancy(string $phone): JsonResponse
    {
        $user = $this->findUserByPhone($phone);

        if (! $user) {
            return response()->json(['error' => 'User tidak ditemukan'], 404);
        }

        $tenancies = $user->tenanciesAsTenant()
            ->where('status', 'aktif')
            ->with([
                'boardingHouse:id,name,address,public_contact_name,public_contact_whatsapp_number',
                'room:id,name,room_number,price,price_period',
            ])
            ->get();

        return response()->json([
            'user_name' => $user->name,
            'tenancies' => $tenancies->map(fn ($t) => [
                'id' => $t->id,
                'kos_name' => $t->boardingHouse->name,
                'kos_address' => $t->boardingHouse->address,
                'kos_contact_name' => $t->boardingHouse->public_contact_name,
                'kos_contact_wa' => $t->boardingHouse->public_contact_whatsapp_number,
                'room_name' => $t->room->name,
                'room_number' => $t->room->room_number,
                'room_price' => (float) $t->room->price,
                'room_price_period' => $t->room->price_period,
                'start_date' => $t->start_date->format('Y-m-d'),
                'end_date' => $t->end_date?->format('Y-m-d'),
                'occupant_count' => $t->occupant_count,
            ]),
        ]);
    }

    public function userInvoices(string $phone): JsonResponse
    {
        $user = $this->findUserByPhone($phone);

        if (! $user) {
            return response()->json(['error' => 'User tidak ditemukan'], 404);
        }

        $invoices = Invoice::where('user_id', $user->id)
            ->whereIn('status', ['belum_dibayar', 'jatuh_tempo', 'menunggu_konfirmasi'])
            ->with('tenancy.boardingHouse:id,name')
            ->latest('due_date')
            ->limit(10)
            ->get();

        return response()->json([
            'user_name' => $user->name,
            'invoices' => $invoices->map(fn ($inv) => [
                'id' => $inv->id,
                'kos_name' => $inv->tenancy?->boardingHouse?->name,
                'period_start' => $inv->period_start->format('Y-m-d'),
                'period_end' => $inv->period_end->format('Y-m-d'),
                'rent_price' => (float) $inv->rent_price,
                'ppn_percent' => (float) $inv->ppn_percent,
                'ppn_amount' => (float) $inv->ppn_amount,
                'total_amount' => (float) $inv->amount,
                'due_date' => $inv->due_date->format('Y-m-d'),
                'status' => $inv->status,
            ]),
            'total_unpaid' => (float) $invoices->whereIn('status', ['belum_dibayar', 'jatuh_tempo'])->sum('amount'),
        ]);
    }

    public function ownerSummary(string $phone): JsonResponse
    {
        $user = $this->findUserByPhone($phone);

        if (! $user || $user->role !== 'admin') {
            return response()->json(['error' => 'Owner tidak ditemukan'], 404);
        }

        $kosList = $user->boardingHouses()
            ->where('status', 'dipublikasikan')
            ->withCount([
                'rooms',
                'rooms as available_rooms_count' => fn ($q) => $q->where('status', 'tersedia'),
                'rooms as occupied_rooms_count' => fn ($q) => $q->where('status', 'terisi'),
                'tenancies as active_tenancies_count' => fn ($q) => $q->where('status', 'aktif'),
            ])
            ->get(['id', 'name', 'address']);

        $unpaidInvoices = Invoice::where('admin_id', $user->id)
            ->whereIn('status', ['belum_dibayar', 'jatuh_tempo'])
            ->count();

        return response()->json([
            'owner_name' => $user->name,
            'kos_list' => $kosList->map(fn ($kos) => [
                'id' => $kos->id,
                'name' => $kos->name,
                'address' => $kos->address,
                'total_rooms' => $kos->rooms_count,
                'available_rooms' => $kos->available_rooms_count,
                'occupied_rooms' => $kos->occupied_rooms_count,
                'active_tenants' => $kos->active_tenancies_count,
            ]),
            'total_kos' => $kosList->count(),
            'unpaid_invoices' => $unpaidInvoices,
        ]);
    }

    public function platformSettings(): JsonResponse
    {
        $keys = [
            'app_name', 'ppn_percentage', 'pph_percentage',
            'contact_email', 'contact_phone', 'contact_address',
            'about_us', 'terms_and_conditions', 'privacy_policy',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::getSetting($key);
        }

        return response()->json($settings);
    }

    private function findUserByPhone(string $phone): ?User
    {
        $normalized = WhatsappNumber::normalize($phone);

        if (! $normalized) {
            return null;
        }

        return User::where('whatsapp_number', $normalized)->first();
    }
}
