<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        return Inertia::render('SuperAdmin/Users/Index', [
            'users' => Inertia::defer(fn () => $query->latest()->paginate(10)->withQueryString()),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Password::defaults()],
            'role' => 'required|in:user,admin,super_admin',
            'status' => 'required|in:aktif,nonaktif,diblokir',
            'whatsapp_number' => 'nullable|string|max:20',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'],
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'email_verified_at' => now(), // Auto verify for admin created users
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:user,admin,super_admin',
            'status' => 'required|in:aktif,nonaktif,diblokir',
            'whatsapp_number' => 'nullable|string|max:20',
        ]);

        if ($user->role !== $validated['role']) {
            if ($user->role === 'admin' && $user->boardingHouses()->exists()) {
                return back()->with('error', 'Tidak dapat mengubah peran: pengguna ini sudah memiliki properti kos.');
            }
            if ($user->role === 'user' && $user->tenanciesAsTenant()->exists()) {
                return back()->with('error', 'Tidak dapat mengubah peran: pengguna ini sudah memiliki riwayat atau tagihan sewa.');
            }
        }

        $user->update($validated);

        if ($request->filled('password')) {
            $request->validate(['password' => ['required', Password::defaults()]]);
            $user->update(['password' => Hash::make($request->password)]);
        }

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun Anda sendiri.');
        }

        // Check active relations (tenancies for tenants, boarding houses for owners)
        if ($user->role === 'user' && $user->tenanciesAsTenant()->where('status', 'aktif')->exists()) {
            return back()->with('error', 'Penyewa masih memiliki sewa aktif.');
        }
        if ($user->role === 'admin' && $user->boardingHouses()->exists()) {
            return back()->with('error', 'Pemilik kos masih memiliki data kos.');
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
