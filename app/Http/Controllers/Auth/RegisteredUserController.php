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
        ], [
            'whatsapp_number.regex' => 'Nomor WhatsApp harus nomor Indonesia yang valid (dimulai dengan 08, +62, atau 62).',
        ]);

        // Normalize WhatsApp number to 62 format
        $whatsappNumber = $request->whatsapp_number;
        if (str_starts_with($whatsappNumber, '+62')) {
            $whatsappNumber = substr($whatsappNumber, 1); // Remove the '+' prefix
        } elseif (str_starts_with($whatsappNumber, '08')) {
            $whatsappNumber = '62' . substr($whatsappNumber, 1); // Replace '0' with '62'
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp_number' => $whatsappNumber,
            'password' => Hash::make($request->password),
            'role' => 'pemilik_kos', // or 'penyewa' depending on PRD, let's use 'pemilik_kos' as PRD says "Jika registrasi publik pemilik kos diaktifkan, role pendaftaran dikunci pemilik_kos"
            'status' => 'aktif',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
