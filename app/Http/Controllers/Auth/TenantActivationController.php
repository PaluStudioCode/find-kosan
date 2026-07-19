<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class TenantActivationController extends Controller
{
    public function show(Request $request, $token)
    {
        $activationToken = UserActivationToken::where('token_hash', hash('sha256', $token))
            ->where('purpose', 'tenant_activation')
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->firstOrFail();

        return inertia('Auth/TenantActivation', ['token' => $token]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $activationToken = UserActivationToken::where('token_hash', hash('sha256', $request->token))
            ->where('purpose', 'tenant_activation')
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->firstOrFail();

        $user = $activationToken->user;
        $user->update([
            'password' => Hash::make($request->password),
            'status' => 'aktif',
            'must_change_password' => false,
        ]);

        $activationToken->update(['used_at' => now()]);

        return redirect()->route('login')->with('status', 'Akun berhasil diaktifkan. Silakan login.');
    }
}
