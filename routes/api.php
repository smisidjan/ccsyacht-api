<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController as TenantAuthController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\System\AuthController;
use App\Http\Controllers\Api\System\TenantController;
use App\Http\Controllers\Api\System\TenantDataController;
use App\Http\Controllers\Api\System\TenantRoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (no tenant header required)
|--------------------------------------------------------------------------
|
| These routes operate on the central database and do not require
| the X-Tenant-ID header.
|
*/

Route::prefix('auth')->group(function () {
    // Email lookup to find organizations for a user
    Route::post('/lookup', [TenantAuthController::class, 'lookup']);
});

// Tenant registration info (public - for registration page)
Route::get('/tenants/{slug}/registration-info', [TenantController::class, 'registrationInfo']);

// Public invitation routes (no tenant middleware - tenant is encoded in token)
Route::get('/invitations/{token}', [InvitationController::class, 'showByToken']);
Route::post('/invitations/accept', [InvitationController::class, 'acceptPublic']);
Route::post('/invitations/decline', [InvitationController::class, 'declinePublic']);

// Admin registration (tenant is encoded in token)
Route::post('/register-admin', [TenantAuthController::class, 'registerAdmin']);

/*
|--------------------------------------------------------------------------
| System API Routes (/api/system/*)
|--------------------------------------------------------------------------
|
| These routes are for system administration.
|
*/

Route::prefix('system')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | System Authentication (Public)
    |--------------------------------------------------------------------------
    */
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    /*
    |--------------------------------------------------------------------------
    | System Authentication (Protected)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:system')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);

        /*
        |--------------------------------------------------------------------------
        | Tenant Management
        |--------------------------------------------------------------------------
        */
        Route::apiResource('tenants', TenantController::class);

        /*
        |--------------------------------------------------------------------------
        | Cross-Tenant Access (requires X-Tenant-ID header)
        |--------------------------------------------------------------------------
        */
        Route::prefix('tenant')->middleware('system-admin-tenant')->group(function () {
            // View tenant users
            Route::get('/users', [TenantDataController::class, 'listUsers']);
            Route::get('/users/{id}', [TenantDataController::class, 'showUser']);

            // Impersonation
            Route::post('/impersonate/{userId}', [TenantDataController::class, 'impersonate']);
            Route::delete('/impersonate/{userId}', [TenantDataController::class, 'endImpersonation']);

            // View tenant invitations
            Route::get('/invitations', [TenantDataController::class, 'listInvitations']);

            // Tenant statistics
            Route::get('/stats', [TenantDataController::class, 'stats']);

            // Role management
            Route::get('/roles', [TenantRoleController::class, 'index']);
            Route::post('/roles', [TenantRoleController::class, 'store']);
            Route::get('/roles/{uuid}', [TenantRoleController::class, 'show']);
            Route::put('/roles/{uuid}', [TenantRoleController::class, 'update']);
            Route::delete('/roles/{uuid}', [TenantRoleController::class, 'destroy']);
            Route::get('/permissions', [TenantRoleController::class, 'permissions']);
            Route::get('/role-types', [TenantRoleController::class, 'types']);
        });
    });
});

