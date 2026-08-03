<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\LandingPageController;
use App\Http\Controllers\Public\PublicKosController;
use App\Http\Controllers\Public\PageController;
use App\Http\Middleware\GuestOrTenant;
use Illuminate\Support\Facades\Route;

Route::middleware([GuestOrTenant::class])->group(function () {
    Route::get('/', [LandingPageController::class, 'index'])->name('home');

    Route::get('/kos', [PublicKosController::class, 'index'])->name('public.kos.index');
    Route::get('/kos/{kos}', [PublicKosController::class, 'show'])->name('public.kos.show');
    Route::get('/{slug}', [PageController::class, 'show'])
        ->whereIn('slug', ['tentang-kami', 'syarat-ketentuan', 'kebijakan-privasi'])
        ->name('page.show');
});
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\KosController as AdminKosController;
use App\Http\Controllers\Admin\KosPhotoController as AdminKosPhotoController;
use App\Http\Controllers\Admin\LegalDocumentController as AdminLegalDocumentController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Admin\TenancyController as AdminTenancyController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WhatsappSettingsController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboard;
use App\Http\Controllers\SuperAdmin\FacilityController;
use App\Http\Controllers\SuperAdmin\MasterDataController;
use App\Http\Controllers\SuperAdmin\RuleController;
use App\Http\Controllers\SuperAdmin\SystemSettingsController;
use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\SuperAdmin\VerificationController as SuperAdminVerificationController;
use App\Http\Controllers\SuperAdmin\WhatsappSettingsController as SuperAdminWhatsappSettingsController;
use App\Http\Controllers\SuperAdmin\WithdrawalController;
use App\Http\Controllers\User\KosReviewController;
use App\Http\Controllers\User\TenancyController;

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Email OTP
    Route::post('/profile/email/send-otp', [ProfileController::class, 'sendEmailOtp'])->name('profile.email.send-otp');
    Route::post('/profile/email/verify-otp', [ProfileController::class, 'verifyEmailOtp'])->name('profile.email.verify-otp');

    // WA OTP
    Route::post('/profile/wa/send-otp', [ProfileController::class, 'sendWaOtp'])->name('profile.wa.send-otp');
    Route::post('/profile/wa/verify-otp', [ProfileController::class, 'verifyWaOtp'])->name('profile.wa.verify-otp');

    Route::middleware(['must_change_password'])->group(function () {
        Route::get('/dashboard', function () {
            $role = auth()->user()->role;
            if ($role === 'super_admin') {
                return redirect()->route('superadmin.dashboard');
            }
            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('home');
        })->name('dashboard');

        // Role Super Admin
        Route::middleware(['role:super_admin'])->prefix('superadmin')->name('superadmin.')->group(function () {
            Route::get('/dashboard', [SuperAdminDashboard::class, 'index'])->name('dashboard');

            Route::get('/verifications', [SuperAdminVerificationController::class, 'index'])->name('verifications.index');
            Route::get('/verifications/{kos}', [SuperAdminVerificationController::class, 'show'])->name('verifications.show');
            Route::post('/verifications/{kos}/approve', [SuperAdminVerificationController::class, 'approve'])->name('verifications.approve');
            Route::post('/verifications/{kos}/reject', [SuperAdminVerificationController::class, 'reject'])->name('verifications.reject');
            Route::get('/verifications/{kos}/document/{document}', [SuperAdminVerificationController::class, 'downloadLegalDoc'])->name('verifications.document');

            Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
            Route::resource('reports', App\Http\Controllers\SuperAdmin\ReportController::class)->only(['index', 'show', 'update', 'destroy']);
            
            Route::get('/financial-reports', [App\Http\Controllers\SuperAdmin\FinancialReportController::class, 'index'])->name('financial-reports.index');
            Route::get('/financial-reports/export-excel', [App\Http\Controllers\SuperAdmin\FinancialReportController::class, 'exportExcel'])->name('financial-reports.export-excel');
            Route::get('/financial-reports/export-pdf', [App\Http\Controllers\SuperAdmin\FinancialReportController::class, 'exportPdf'])->name('financial-reports.export-pdf');


            Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
            Route::resource('facilities', FacilityController::class)->except(['create', 'edit', 'show', 'index']);
            Route::resource('rules', RuleController::class)->except(['create', 'edit', 'show', 'index']);

            Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
            Route::get('/withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show');
            Route::post('/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
            Route::post('/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');
            Route::post('/withdrawals/{withdrawal}/complete', [WithdrawalController::class, 'complete'])->name('withdrawals.complete');

            // WhatsApp Settings (SuperAdmin/System) API
            Route::post('/whatsapp-settings/start', [SuperAdminWhatsappSettingsController::class, 'startSession'])->name('whatsapp.start');
            Route::post('/whatsapp-settings/start-pairing', [SuperAdminWhatsappSettingsController::class, 'startPairingCode'])->name('whatsapp.start-pairing');
            Route::post('/whatsapp-settings/stop', [SuperAdminWhatsappSettingsController::class, 'stopSession'])->name('whatsapp.stop');
            Route::get('/whatsapp-settings/status', [SuperAdminWhatsappSettingsController::class, 'getStatus'])->name('whatsapp.status');
            Route::get('/whatsapp-settings/qr', [SuperAdminWhatsappSettingsController::class, 'getQrCode'])->name('whatsapp.qr');

            // System Settings
            Route::get('/settings', [SystemSettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SystemSettingsController::class, 'update'])->name('settings.update');
        });

        // Role Admin (Dulunya Pemilik Kos)
        Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

            // Kos Property Management
            Route::resource('kos', AdminKosController::class)->parameters(['kos' => 'kos'])->withTrashed();
            Route::post('kos/{kos}/rooms/bulk', [AdminRoomController::class, 'bulkStore'])->name('kos.rooms.bulk');
            Route::resource('kos.rooms', AdminRoomController::class)->except(['index', 'show'])->parameters(['kos' => 'kos', 'rooms' => 'room']);
            Route::post('kos/{kos}/photos', [AdminKosPhotoController::class, 'store'])->name('kos.photos.store');
            Route::put('kos/{kos}/photos/{photo}', [AdminKosPhotoController::class, 'update'])->name('kos.photos.update');
            Route::delete('kos/{kos}/photos/{photo}', [AdminKosPhotoController::class, 'destroy'])->name('kos.photos.destroy');

            Route::post('kos/{kos}/legal-documents', [AdminLegalDocumentController::class, 'store'])->name('kos.legal-documents.store');
            Route::delete('kos/{kos}/legal-documents/{legalDocument}', [AdminLegalDocumentController::class, 'destroy'])->name('kos.legal-documents.destroy');
            Route::post('kos/{kos}/verify', [AdminKosController::class, 'requestVerification'])->name('kos.verify');

            // Tenancies Management
            Route::get('/tenancies', [AdminTenancyController::class, 'index'])->name('tenancies.index');
            Route::get('/tenancies/{tenancy}', [AdminTenancyController::class, 'show'])->name('tenancies.show');
            Route::post('/tenancies/{tenancy}/end', [AdminTenancyController::class, 'endTenancy'])->name('tenancies.end');

            Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
            Route::get('/wallet/export', [WalletController::class, 'export'])->name('wallet.export');
            Route::post('/wallet/withdrawals', [WalletController::class, 'storeWithdrawal'])->name('wallet.withdrawals.store');

            Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');

            // WhatsApp Settings
            Route::get('/whatsapp-settings', [WhatsappSettingsController::class, 'index'])->name('whatsapp.index');
            Route::post('/whatsapp-settings/start', [WhatsappSettingsController::class, 'startSession'])->name('whatsapp.start');
            Route::post('/whatsapp-settings/start-pairing', [WhatsappSettingsController::class, 'startPairingCode'])->name('whatsapp.start-pairing');
            Route::post('/whatsapp-settings/stop', [WhatsappSettingsController::class, 'stopSession'])->name('whatsapp.stop');
            Route::get('/whatsapp-settings/status', [WhatsappSettingsController::class, 'getStatus'])->name('whatsapp.status');
            Route::get('/whatsapp-settings/qr', [WhatsappSettingsController::class, 'getQrCode'])->name('whatsapp.qr');
        });

        // Role User (Dulunya Penyewa)
        Route::middleware(['role:user'])->prefix('user')->name('user.')->group(function () {
            // Tenancies Management
            Route::post('/rooms/{room}/book', [TenancyController::class, 'store'])->name('tenancies.store');
            Route::get('/tenancies', [TenancyController::class, 'index'])->name('tenancies.index');
            Route::get('/tenancies/{tenancy}', [TenancyController::class, 'show'])->name('tenancies.show');
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
Route::middleware(['auth', 'active', 'must_change_password', 'role:user'])->group(function () {
    Route::post('/duitku/create-invoice', [PaymentGatewayController::class, 'createInvoice'])->name('duitku.create-invoice');
    Route::post('/duitku/verify-local', [PaymentGatewayController::class, 'verifyLocal'])->name('duitku.verify-local');
});
Route::post('/duitku/callback', [PaymentGatewayController::class, 'callback'])->name('duitku.callback');

// Google Login
Route::middleware('guest')->group(function () {
    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('google.login');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback']);
});

require __DIR__.'/auth.php';
