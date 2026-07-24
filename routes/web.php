<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicKosController;
use App\Http\Middleware\GuestOrTenant;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware([GuestOrTenant::class])->group(function () {
    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('home');

    Route::get('/kos', [PublicKosController::class, 'index'])->name('public.kos.index');
    Route::get('/kos/{kos}', [PublicKosController::class, 'show'])->name('public.kos.show');
});
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboard;
use App\Http\Controllers\Owner\KosController as OwnerKosController;
use App\Http\Controllers\Owner\KosPhotoController as OwnerKosPhotoController;
use App\Http\Controllers\Owner\LegalDocumentController as OwnerLegalDocumentController;
use App\Http\Controllers\Owner\RoomController as OwnerRoomController;
use App\Http\Controllers\Owner\TenancyController;
use App\Http\Controllers\Owner\WalletController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Tenant\KosReviewController;

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['must_change_password'])->group(function () {
        Route::get('/dashboard', function () {
            $role = auth()->user()->role;
            if ($role === 'super_admin') {
                return redirect()->route('admin.dashboard');
            }
            if ($role === 'pemilik_kos') {
                return redirect()->route('owner.dashboard');
            }

            return redirect()->route('home');
        })->name('dashboard');

        // Role Super Admin
        Route::middleware(['role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

            Route::get('/verifications', [AdminVerificationController::class, 'index'])->name('verifications.index');
            Route::get('/verifications/{kos}', [AdminVerificationController::class, 'show'])->name('verifications.show');
            Route::post('/verifications/{kos}/approve', [AdminVerificationController::class, 'approve'])->name('verifications.approve');
            Route::post('/verifications/{kos}/reject', [AdminVerificationController::class, 'reject'])->name('verifications.reject');
            Route::get('/verifications/{kos}/document/{document}', [AdminVerificationController::class, 'downloadLegalDoc'])->name('verifications.document');

            Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
            Route::resource('reports', App\Http\Controllers\Admin\ReportController::class)->only(['index', 'show', 'update']);
            Route::resource('facilities', FacilityController::class)->except(['create', 'edit', 'show']);
            Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
            Route::get('/withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show');
            Route::post('/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
            Route::post('/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');
            Route::post('/withdrawals/{withdrawal}/complete', [WithdrawalController::class, 'complete'])->name('withdrawals.complete');
        });

        // Role Pemilik Kos
        Route::middleware(['role:pemilik_kos'])->prefix('owner')->name('owner.')->group(function () {
            Route::get('/dashboard', [OwnerDashboard::class, 'index'])->name('dashboard');

            // Kos Property Management
            Route::resource('kos', OwnerKosController::class)->parameters(['kos' => 'kos']);
            Route::resource('kos.rooms', OwnerRoomController::class)->except(['index', 'show'])->parameters(['kos' => 'kos', 'rooms' => 'room']);
            Route::post('kos/{kos}/photos', [OwnerKosPhotoController::class, 'store'])->name('kos.photos.store');
            Route::put('kos/{kos}/photos/{photo}', [OwnerKosPhotoController::class, 'update'])->name('kos.photos.update');
            Route::delete('kos/{kos}/photos/{photo}', [OwnerKosPhotoController::class, 'destroy'])->name('kos.photos.destroy');
            Route::post('kos/{kos}/qris', [OwnerKosController::class, 'uploadQris'])->name('kos.qris');
            Route::delete('kos/{kos}/qris', [OwnerKosController::class, 'deleteQris'])->name('kos.qris.destroy');
            Route::post('kos/{kos}/legal-documents', [OwnerLegalDocumentController::class, 'store'])->name('kos.legal-documents.store');
            Route::delete('kos/{kos}/legal-documents/{legalDocument}', [OwnerLegalDocumentController::class, 'destroy'])->name('kos.legal-documents.destroy');
            Route::post('kos/{kos}/verify', [OwnerKosController::class, 'requestVerification'])->name('kos.verify');

            // Tenancies Management
            Route::get('/tenancies', [TenancyController::class, 'index'])->name('tenancies.index');
            Route::get('/tenancies/{tenancy}', [TenancyController::class, 'show'])->name('tenancies.show');
            Route::post('/tenancies/{tenancy}/end', [TenancyController::class, 'endTenancy'])->name('tenancies.end');
            Route::post('/payments/{payment}/confirm', [TenancyController::class, 'confirmPayment'])->name('payments.confirm');

            Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
            Route::post('/wallet/withdrawals', [WalletController::class, 'storeWithdrawal'])->name('wallet.withdrawals.store');
        });

        // Role Penyewa
        Route::middleware(['role:penyewa'])->prefix('tenant')->name('tenant.')->group(function () {
            // Tenancies Management
            Route::post('/rooms/{room}/book', [App\Http\Controllers\Tenant\TenancyController::class, 'store'])->name('tenancies.store');
            Route::get('/tenancies', [App\Http\Controllers\Tenant\TenancyController::class, 'index'])->name('tenancies.index');
            Route::get('/tenancies/{tenancy}', [App\Http\Controllers\Tenant\TenancyController::class, 'show'])->name('tenancies.show');
            Route::post('/invoices/{invoice}/payment', [App\Http\Controllers\Tenant\TenancyController::class, 'uploadPayment'])->name('invoices.payment');
            Route::post('/kos/{kos}/review', [KosReviewController::class, 'store'])->name('kos.reviews.store');
        });
    });
});

// Authenticated User Routes (Pemilik & Penyewa & Admin)
Route::middleware(['auth'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');

    // Secure media access
    Route::get('/media/{type}/{filename}', [MediaController::class, 'show'])->name('media.show');
});

// Region API
Route::prefix('api/regions')->group(function () {
    Route::get('/provinces', [RegionController::class, 'provinces']);
    Route::get('/cities', [RegionController::class, 'cities']);
    Route::get('/districts', [RegionController::class, 'districts']);
    Route::get('/villages', [RegionController::class, 'villages']);
    Route::post('/match', [RegionController::class, 'reverseGeocodeMatch']);
});

// Duitku API
Route::middleware(['auth', 'active', 'must_change_password', 'role:penyewa'])->group(function () {
    Route::post('/duitku/create-invoice', [PaymentGatewayController::class, 'createInvoice'])->name('duitku.create-invoice');
    Route::post('/duitku/verify-local', [PaymentGatewayController::class, 'verifyLocal'])->name('duitku.verify-local');
});
Route::post('/duitku/callback', [PaymentGatewayController::class, 'callback'])->name('duitku.callback');

require __DIR__.'/auth.php';
