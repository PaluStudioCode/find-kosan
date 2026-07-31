<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            // Bypass SSL verification for local development (Laragon/cURL 60 error)
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->setHttpClient(new Client(['verify' => false]))
                ->user();

            $user = User::where('google_id', $googleUser->id)->first();

            if ($user) {
                Auth::login($user);

                return redirect()->intended(route('dashboard', absolute: false));
            }

            // check if user with same email exists
            $userWithEmail = User::where('email', $googleUser->email)->first();

            if ($userWithEmail) {
                // Link google account to existing user
                $userWithEmail->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);
                Auth::login($userWithEmail);

                return redirect()->intended(route('dashboard', absolute: false));
            }

            // Create new user
            $newUser = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
                'role' => 'user', // default role
                'status' => 'aktif',
                'password' => null, // no password for google login
                'email_verified_at' => now(), // Assume email is verified if logging in with google
            ]);

            Auth::login($newUser);

            return redirect()->intended(route('dashboard', absolute: false));

        } catch (\Exception $e) {
            Log::error('Google Login Error: '.$e->getMessage());

            return redirect('/login')->with('error', 'Terjadi kesalahan saat login menggunakan Google. Silakan coba lagi.');
        }
    }
}
