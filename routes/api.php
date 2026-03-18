<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController as TenantAuthController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\System\AuthController;
use App\Http\Controllers\Api\System\TenantController;
use App\Http\Controllers\Api\System\TenantDataController;
use App\Http\Controllers\Api\System\TenantProjectController;
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
        Route::get('/selectable-permissions', [TenantController::class, 'selectablePermissions']);

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

            // Project management
            Route::get('/projects', [TenantProjectController::class, 'index']);
            Route::post('/projects', [TenantProjectController::class, 'store']);
            Route::get('/projects/{uuid}', [TenantProjectController::class, 'show']);
            Route::put('/projects/{uuid}', [TenantProjectController::class, 'update']);
            Route::delete('/projects/{uuid}', [TenantProjectController::class, 'destroy']);

            // General Arrangement
            Route::post('/projects/{uuid}/general-arrangement', [TenantProjectController::class, 'uploadGeneralArrangement']);
            Route::get('/projects/{uuid}/general-arrangement', [TenantProjectController::class, 'downloadGeneralArrangement']);
            Route::delete('/projects/{uuid}/general-arrangement', [TenantProjectController::class, 'deleteGeneralArrangement']);

            // Document Types
            Route::get('/projects/{uuid}/document-types', [TenantProjectController::class, 'documentTypes']);
            Route::post('/projects/{uuid}/document-types', [TenantProjectController::class, 'storeDocumentType']);
            Route::put('/projects/{uuid}/document-types/{typeId}', [TenantProjectController::class, 'updateDocumentType']);
            Route::delete('/projects/{uuid}/document-types/{typeId}', [TenantProjectController::class, 'destroyDocumentType']);

            // Documents
            Route::get('/projects/{uuid}/documents', [TenantProjectController::class, 'documents']);
            Route::post('/projects/{uuid}/document-types/{typeId}/documents', [TenantProjectController::class, 'storeDocument']);
            Route::get('/projects/{uuid}/documents/{docId}', [TenantProjectController::class, 'downloadDocument']);
            Route::delete('/projects/{uuid}/documents/{docId}', [TenantProjectController::class, 'destroyDocument']);

            // Shipyards
            Route::get('/shipyards', [TenantProjectController::class, 'shipyards']);
            Route::post('/shipyards', [TenantProjectController::class, 'storeShipyard']);
            Route::get('/shipyards/{id}', [TenantProjectController::class, 'showShipyard']);
            Route::put('/shipyards/{id}', [TenantProjectController::class, 'updateShipyard']);
            Route::delete('/shipyards/{id}', [TenantProjectController::class, 'destroyShipyard']);
        });
    });
});

