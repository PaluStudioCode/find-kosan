<?php

use App\Http\Controllers\Api\AiDataController;
use Illuminate\Support\Facades\Route;

Route::middleware('internal_api')->prefix('ai')->group(function () {
    // Endpoints for AI Tool Calling
    Route::get('/identify-user/{phone}', [AiDataController::class, 'identifyUser']);
    Route::get('/search-kos', [AiDataController::class, 'searchKos']);
    Route::get('/kos/{id}', [AiDataController::class, 'kosDetail']);
    Route::get('/kos/{id}/rooms', [AiDataController::class, 'kosRooms']);
    Route::get('/user/{phone}/tenancy', [AiDataController::class, 'userTenancy']);
    Route::post('/user/{phone}/report', [AiDataController::class, 'submitTenantReport'])->name('ai.user.report');
    Route::get('/user/{phone}/invoices', [AiDataController::class, 'userInvoices']);
    Route::get('/owner/{phone}/summary', [AiDataController::class, 'ownerSummary']);
    Route::get('/settings', [AiDataController::class, 'platformSettings']);

    // Endpoints for OTP / Security Link
    Route::post('/request-otp', [AiDataController::class, 'requestOtp']);
    Route::post('/verify-otp', [AiDataController::class, 'verifyOtp']);
});
