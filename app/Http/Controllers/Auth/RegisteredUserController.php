<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\Cache;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|string|lowercase|email|max:150|unique:'.User::class,
            'whatsapp_number' => ['required', 'string', 'regex:/^(\+62|62|08)\d{8,13}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:user,admin'],
        ], [
            'whatsapp_number.regex' => 'Nomor WhatsApp harus nomor Indonesia yang valid (dimulai dengan 08, +62, atau 62).',
            'role.required' => 'Silakan pilih jenis akun.',
            'role.in' => 'Jenis akun tidak valid.',
        ]);

        // Normalize WhatsApp number to 62 format
        $whatsappNumber = $request->whatsapp_number;
        if (str_starts_with($whatsappNumber, '+62')) {
            $whatsappNumber = substr($whatsappNumber, 1);
        } elseif (str_starts_with($whatsappNumber, '08')) {
            $whatsappNumber = '62'.substr($whatsappNumber, 1);
        }

        $otp = (string) rand(100000, 999999);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp_number' => $whatsappNumber,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => 'aktif',
            'otp' => $otp,
        ];

        Cache::put('register_otp_' . $whatsappNumber, $userData, now()->addMinutes(5));

        $waService = new WhatsappService();
        $message = "Kode OTP pendaftaran CariKosan Anda adalah: *{$otp}*\nBerlaku selama 5 menit.";
        
        // Asumsi adminId = 0 (Superadmin Session ID)
        $response = $waService->sendMessage(0, $whatsappNumber, $message);

        if (empty($response['status']) || !$response['status']) {
            Cache::forget('register_otp_' . $whatsappNumber);
            throw ValidationException::withMessages([
                'whatsapp_number' => 'Gagal mengirim OTP ke nomor WA ini. Pastikan nomor valid atau layanan WA sedang aktif.',
            ]);
        }

        return back()->with([
            'otp_sent' => true,
            'whatsapp_number' => $whatsappNumber,
            'status' => 'OTP berhasil dikirim ke nomor WhatsApp Anda.'
        ]);
    }

    /**
     * Verify the OTP and finalize registration.
     *
     * @throws ValidationException
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'whatsapp_number' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $userData = Cache::get('register_otp_' . $request->whatsapp_number);

        if (!$userData) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP sudah kedaluwarsa atau tidak valid.',
            ]);
        }

        if ($userData['otp'] !== $request->otp) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP yang Anda masukkan salah.',
            ]);
        }

        // OTP Valid, create user
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'whatsapp_number' => $userData['whatsapp_number'],
            'password' => $userData['password'],
            'role' => $userData['role'],
            'status' => $userData['status'],
        ]);

        Cache::forget('register_otp_' . $request->whatsapp_number);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
