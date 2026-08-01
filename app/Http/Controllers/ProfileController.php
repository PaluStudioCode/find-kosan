<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use App\Notifications\EmailVerificationOtp;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Generate and send Email OTP
     */
    public function sendEmailOtp(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('success', 'Email Anda sudah terverifikasi.');
        }

        $otp = (string) rand(100000, 999999);
        
        Cache::put('email_otp_' . $user->id, $otp, now()->addMinutes(5));

        try {
            $user->notify(new EmailVerificationOtp($otp));
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'email' => 'Gagal mengirim OTP ke email. Pastikan email valid atau layanan email sedang aktif.',
            ]);
        }

        return back()->with([
            'email_otp_sent' => true,
            'status' => 'OTP berhasil dikirim ke Email Anda.'
        ]);
    }

    /**
     * Verify the Email OTP
     */
    public function verifyEmailOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('success', 'Email Anda sudah terverifikasi.');
        }

        $cachedOtp = Cache::get('email_otp_' . $user->id);

        if (!$cachedOtp) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP sudah kedaluwarsa atau tidak valid.',
            ]);
        }

        if ($cachedOtp !== $request->otp) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP yang Anda masukkan salah.',
            ]);
        }

        // OTP Valid, verify email
        $user->email_verified_at = now();
        $user->save();

        Cache::forget('email_otp_' . $user->id);

        return back()->with('success', 'Email berhasil diverifikasi!');
    }

    /**
     * Generate and send WA OTP
     */
    public function sendWaOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'whatsapp_number' => ['required', 'string', 'regex:/^(\+62|62|08)\d{8,13}$/'],
        ], [
            'whatsapp_number.regex' => 'Nomor WhatsApp harus nomor Indonesia yang valid (dimulai dengan 08, +62, atau 62).',
        ]);

        $user = $request->user();

        // Normalize WhatsApp number
        $whatsappNumber = $request->whatsapp_number;
        if (str_starts_with($whatsappNumber, '+62')) {
            $whatsappNumber = substr($whatsappNumber, 1);
        } elseif (str_starts_with($whatsappNumber, '08')) {
            $whatsappNumber = '62'.substr($whatsappNumber, 1);
        }

        // Check if phone number is already used by another user
        $existing = \App\Models\User::where('whatsapp_number', $whatsappNumber)->where('id', '!=', $user->id)->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'Nomor WhatsApp ini sudah digunakan oleh akun lain.',
            ]);
        }

        $otp = (string) rand(100000, 999999);
        
        Cache::put('wa_otp_' . $user->id, ['otp' => $otp, 'whatsapp_number' => $whatsappNumber], now()->addMinutes(5));

        $waService = new \App\Services\WhatsappService();
        $message = "Kode OTP verifikasi WhatsApp CariKosan Anda adalah: *{$otp}*\nBerlaku selama 5 menit.";
        
        $response = $waService->sendMessage(0, $whatsappNumber, $message);

        if (empty($response['status']) || !$response['status']) {
            Cache::forget('wa_otp_' . $user->id);
            throw ValidationException::withMessages([
                'whatsapp_number' => 'Gagal mengirim OTP ke nomor WA ini. Pastikan nomor valid atau layanan WA sedang aktif.',
            ]);
        }

        return back()->with([
            'wa_otp_sent' => true,
            'whatsapp_number' => $whatsappNumber,
            'status' => 'OTP berhasil dikirim ke nomor WhatsApp Anda.'
        ]);
    }

    /**
     * Verify the WA OTP
     */
    public function verifyWaOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();

        $cachedData = Cache::get('wa_otp_' . $user->id);

        if (!$cachedData) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP sudah kedaluwarsa atau tidak valid.',
            ]);
        }

        if ($cachedData['otp'] !== $request->otp) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP yang Anda masukkan salah.',
            ]);
        }

        // OTP Valid, update user's whatsapp number
        $user->whatsapp_number = $cachedData['whatsapp_number'];
        $user->save();

        Cache::forget('wa_otp_' . $user->id);

        return back()->with('success', 'Nomor WhatsApp berhasil diverifikasi dan disimpan!');
    }
}
