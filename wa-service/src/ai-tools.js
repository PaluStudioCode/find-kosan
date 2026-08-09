const LARAVEL_API_URL = (process.env.LARAVEL_API_URL || 'http://127.0.0.1:8000').replace(/\/+$/, '');
const LARAVEL_API_KEY = process.env.LARAVEL_API_KEY || process.env.WA_SERVICE_API_KEY || process.env.API_KEY || '';
const configuredTimeout = Number.parseInt(process.env.LARAVEL_API_TIMEOUT_MS || '10000', 10);
const LARAVEL_API_TIMEOUT_MS = Number.isFinite(configuredTimeout) && configuredTimeout > 0 ? configuredTimeout : 10000;

async function requestLaravelApi(endpoint, { method = 'GET', params = {}, body = null } = {}) {
    const url = new URL(`${LARAVEL_API_URL}/api/ai${endpoint}`);
    for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null && value !== '') {
            url.searchParams.set(key, value);
        }
    }

    const options = {
        method,
        headers: {
            'X-Internal-API-Key': LARAVEL_API_KEY,
            'Accept': 'application/json',
        },
        signal: AbortSignal.timeout(LARAVEL_API_TIMEOUT_MS),
    };

    if (body !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    let response;
    try {
        response = await fetch(url.toString(), options);
    } catch (error) {
        const reason = error.cause?.message || error.message;
        console.error(`[AI-Tools] Laravel API request failed: ${method} ${url.toString()} (${reason})`);
        throw new Error(`Laravel API tidak dapat dihubungi: ${reason}`);
    }

    if (!response.ok) {
        const text = await response.text();
        throw new Error(`Laravel API error ${response.status}: ${text}`);
    }

    return response.json();
}

async function callLaravelApi(endpoint, params = {}) {
    return requestLaravelApi(endpoint, { params });
}

const toolDefinitions = [
    {
        type: 'function',
        function: {
            name: 'identify_user',
            description: 'Identifikasi pengirim pesan berdasarkan nomor WhatsApp. Mengembalikan role (guest/user/admin/super_admin) dan info dasar user. Panggil ini di awal percakapan baru untuk mengetahui siapa yang sedang chat.',
            parameters: {
                type: 'object',
                properties: {
                    phone_number: {
                        type: 'string',
                        description: 'Nomor WhatsApp pengirim (format 62xxx)',
                    },
                },
                required: ['phone_number'],
            },
        },
    },
    {
        type: 'function',
        function: {
            name: 'search_kos',
            description: 'Cari kos/boarding house berdasarkan lokasi, harga, atau fasilitas. Gunakan parameter district/subdistrict jika user menyebut landmark yang kamu tahu lokasinya (misal UNTAD = Palu Selatan).',
            parameters: {
                type: 'object',
                properties: {
                    keyword: {
                        type: 'string',
                        description: 'Kata kunci pencarian (nama kos, landmark, alamat)',
                    },
                    city: {
                        type: 'string',
                        description: 'Nama kota (misal: KOTA PALU)',
                    },
                    district: {
                        type: 'string',
                        description: 'Nama kecamatan (misal: PALU SELATAN)',
                    },
                    subdistrict: {
                        type: 'string',
                        description: 'Nama kelurahan',
                    },
                    max_price: {
                        type: 'number',
                        description: 'Harga maksimum per bulan dalam Rupiah',
                    },
                    facility: {
                        type: 'string',
                        description: 'Nama fasilitas yang dicari (misal: WiFi, AC, Parkir)',
                    },
                },
            },
        },
    },
    {
        type: 'function',
        function: {
            name: 'get_kos_detail',
            description: 'Ambil detail lengkap satu kos: deskripsi, alamat, semua kamar, fasilitas, aturan, review, dan kontak pemilik.',
            parameters: {
                type: 'object',
                properties: {
                    kos_id: {
                        type: 'number',
                        description: 'ID kos yang ingin dilihat detailnya',
                    },
                },
                required: ['kos_id'],
            },
        },
    },
    {
        type: 'function',
        function: {
            name: 'get_room_availability',
            description: 'Cek kamar yang tersedia di kos tertentu beserta harga dan fasilitasnya.',
            parameters: {
                type: 'object',
                properties: {
                    kos_id: {
                        type: 'number',
                        description: 'ID kos yang ingin dicek ketersediaan kamarnya',
                    },
                },
                required: ['kos_id'],
            },
        },
    },
    {
        type: 'function',
        function: {
            name: 'get_user_tenancy',
            description: 'Ambil info penyewaan aktif seorang tenant/penyewa: kos mana, kamar berapa, masa sewa, kontak pemilik. Hanya bisa digunakan untuk user dengan role "user".',
            parameters: {
                type: 'object',
                properties: {
                    phone_number: {
                        type: 'string',
                        description: 'Nomor WhatsApp penyewa (format 62xxx)',
                    },
                },
                required: ['phone_number'],
            },
        },
    },
    {
        type: 'function',
        function: {
            name: 'get_user_invoices',
            description: 'Ambil tagihan yang belum dibayar atau menunggu konfirmasi untuk seorang tenant/penyewa. Hanya bisa digunakan untuk user dengan role "user".',
            parameters: {
                type: 'object',
                properties: {
                    phone_number: {
                        type: 'string',
                        description: 'Nomor WhatsApp penyewa (format 62xxx)',
                    },
                },
                required: ['phone_number'],
            },
        },
    },
    {
        type: 'function',
        function: {
            name: 'get_owner_summary',
            description: 'Ambil ringkasan kos milik pemilik/admin: daftar kos, jumlah kamar, okupansi, jumlah penyewa aktif, tagihan belum dibayar, saldo dompet aktif, dan saldo yang sedang dalam proses penarikan (pending_withdrawal). Hanya bisa digunakan untuk user dengan role "admin".',
            parameters: {
                type: 'object',
                properties: {
                    phone_number: {
                        type: 'string',
                        description: 'Nomor WhatsApp pemilik kos (format 62xxx)',
                    },
                },
                required: ['phone_number'],
            },
        },
    },
    {
        type: 'function',
        function: {
            name: 'get_platform_info',
            description: 'Ambil informasi umum platform: nama aplikasi, PPN, kontak, tentang kami, syarat & ketentuan.',
            parameters: {
                type: 'object',
                properties: {},
            },
        },
    },
];

const toolExecutors = {
    async identify_user(args) {
        return callLaravelApi(`/identify-user/${encodeURIComponent(args.phone_number)}`);
    },

    async search_kos(args) {
        return callLaravelApi('/search-kos', {
            keyword: args.keyword,
            city: args.city,
            district: args.district,
            subdistrict: args.subdistrict,
            max_price: args.max_price,
            facility: args.facility,
        });
    },

    async get_kos_detail(args) {
        return callLaravelApi(`/kos/${args.kos_id}`);
    },

    async get_room_availability(args) {
        return callLaravelApi(`/kos/${args.kos_id}/rooms`);
    },

    async get_user_tenancy(args) {
        return callLaravelApi(`/user/${encodeURIComponent(args.phone_number)}/tenancy`);
    },

    async get_user_invoices(args) {
        return callLaravelApi(`/user/${encodeURIComponent(args.phone_number)}/invoices`);
    },

    async get_owner_summary(args) {
        return callLaravelApi(`/owner/${encodeURIComponent(args.phone_number)}/summary`);
    },

    async get_platform_info() {
        return callLaravelApi('/settings');
    },
};

async function executeTool(name, argsJson) {
    const executor = toolExecutors[name];
    if (!executor) {
        return { error: `Unknown tool: ${name}` };
    }

    try {
        const args = typeof argsJson === 'string' ? JSON.parse(argsJson) : argsJson;
        return await executor(args);
    } catch (error) {
        console.error(`[AI-Tools] Error executing ${name}:`, error.message);
        return { error: error.message };
    }
}

module.exports = { toolDefinitions, executeTool, requestLaravelApi };
