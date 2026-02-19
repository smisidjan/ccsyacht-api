<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
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
        | Admin/Main User/Invitation Manager Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:admin|main user|invitation manager')->group(function () {
            // Invitations Management
            Route::get('/invitations', [InvitationController::class, 'index']);
            Route::post('/invitations', [InvitationController::class, 'store']);
            Route::post('/invitations/{id}/resend', [InvitationController::class, 'resend']);
            Route::delete('/invitations/{id}', [InvitationController::class, 'cancel']);

            // Registration Requests Management
            Route::get('/registration-requests', [RegistrationRequestController::class, 'index']);
            Route::get('/registration-requests/{id}', [RegistrationRequestController::class, 'show']);
            Route::post('/registration-requests/{id}/process', [RegistrationRequestController::class, 'process']);
        });

        /*
        |--------------------------------------------------------------------------
        | Admin/Main User Routes (User Management)
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:admin|main user')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });
    });
});
