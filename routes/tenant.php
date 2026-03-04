<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuestRolePermissionController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\RegistrationRequestController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant API Routes (/api/*)
|--------------------------------------------------------------------------
|
| All routes in this file require the X-Tenant-ID header.
| These routes operate within the tenant's database context.
|
*/

Route::prefix('api')->middleware('tenant')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication Routes (/api/auth/*)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        // Public
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [RegistrationRequestController::class, 'store']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        // Protected
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Authentication Required)
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | User Management
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_users')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{id}', [UserController::class, 'show']);
        });

        Route::middleware('permission:edit_users')->group(function () {
            Route::put('/users/{id}', [UserController::class, 'update']);
        });

        Route::middleware('permission:delete_users')->group(function () {
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Invitation Management
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_invitations')->group(function () {
            Route::get('/invitations', [InvitationController::class, 'index']);
        });

        Route::middleware('permission:create_invitations')->group(function () {
            Route::post('/invitations', [InvitationController::class, 'store']);
        });

        Route::middleware('permission:manage_invitations')->group(function () {
            Route::post('/invitations/{id}/resend', [InvitationController::class, 'resend']);
            Route::delete('/invitations/{id}', [InvitationController::class, 'cancel']);
        });

        /*
        |--------------------------------------------------------------------------
        | Registration Request Management
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_registrations')->group(function () {
            Route::get('/registration-requests', [RegistrationRequestController::class, 'index']);
            Route::get('/registration-requests/{id}', [RegistrationRequestController::class, 'show']);
        });

        Route::middleware('permission:process_registrations')->group(function () {
            Route::post('/registration-requests/{id}/process', [RegistrationRequestController::class, 'process']);
        });

        /*
        |--------------------------------------------------------------------------
        | Organization Settings (Master tenant only)
        |--------------------------------------------------------------------------
        */
        Route::middleware(['master-tenant', 'permission:manage_guest_roles'])->group(function () {
            Route::get('/guest-role-permissions', [GuestRolePermissionController::class, 'index']);
            Route::post('/guest-role-permissions', [GuestRolePermissionController::class, 'store']);
            Route::post('/guest-role-permissions/add', [GuestRolePermissionController::class, 'addRole']);
            Route::delete('/guest-role-permissions/{roleName}', [GuestRolePermissionController::class, 'removeRole']);
            Route::post('/guest-role-permissions/reset', [GuestRolePermissionController::class, 'reset']);
        });

    });
});
