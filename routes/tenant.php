<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuestRolePermissionController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\Project\AreaController;
use App\Http\Controllers\Api\Project\DeckController;
use App\Http\Controllers\Api\Project\DocumentController;
use App\Http\Controllers\Api\Project\DocumentTypeController;
use App\Http\Controllers\Api\Project\LogbookController;
use App\Http\Controllers\Api\Project\MemberController;
use App\Http\Controllers\Api\Project\SignerController;
use App\Http\Controllers\Api\Project\StageController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\RegistrationRequestController;
use App\Http\Controllers\Api\ShipyardController;
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
        | Shipyard Management
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_shipyards')->group(function () {
            Route::get('/shipyards', [ShipyardController::class, 'index']);
            Route::get('/shipyards/{id}', [ShipyardController::class, 'show']);
        });

        Route::middleware('permission:create_shipyards')->group(function () {
            Route::post('/shipyards', [ShipyardController::class, 'store']);
        });

        Route::middleware('permission:edit_shipyards')->group(function () {
            Route::put('/shipyards/{id}', [ShipyardController::class, 'update']);
        });

        Route::middleware('permission:delete_shipyards')->group(function () {
            Route::delete('/shipyards/{id}', [ShipyardController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Project Management
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_projects')->group(function () {
            Route::get('/projects', [ProjectController::class, 'index']);
            Route::get('/projects/{id}', [ProjectController::class, 'show']);
            Route::get('/projects/{id}/general-arrangement', [ProjectController::class, 'downloadGeneralArrangement']);
        });

        Route::middleware('permission:create_projects')->group(function () {
            Route::post('/projects', [ProjectController::class, 'store']);
        });

        Route::middleware('permission:edit_projects')->group(function () {
            Route::put('/projects/{id}', [ProjectController::class, 'update']);
            Route::post('/projects/{id}/general-arrangement', [ProjectController::class, 'uploadGeneralArrangement']);
        });

        Route::middleware('permission:delete_projects')->group(function () {
            Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Project Document Types
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_document_types')->group(function () {
            Route::get('/projects/{projectId}/document-types', [DocumentTypeController::class, 'index']);
            Route::get('/projects/{projectId}/document-types/{typeId}', [DocumentTypeController::class, 'show']);
        });

        Route::middleware('permission:create_document_types')->group(function () {
            Route::post('/projects/{projectId}/document-types', [DocumentTypeController::class, 'store']);
        });

        Route::middleware('permission:edit_document_types')->group(function () {
            Route::put('/projects/{projectId}/document-types/{typeId}', [DocumentTypeController::class, 'update']);
        });

        Route::middleware('permission:delete_document_types')->group(function () {
            Route::delete('/projects/{projectId}/document-types/{typeId}', [DocumentTypeController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Project Documents
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_documents')->group(function () {
            Route::get('/projects/{projectId}/documents', [DocumentController::class, 'index']);
            Route::get('/projects/{projectId}/documents/{docId}', [DocumentController::class, 'show']);
            Route::get('/projects/{projectId}/document-types/{typeId}/documents', [DocumentController::class, 'indexByType']);
        });

        Route::middleware('permission:download_documents')->group(function () {
            Route::get('/projects/{projectId}/documents/{docId}/download', [DocumentController::class, 'download']);
        });

        Route::middleware('permission:upload_documents')->group(function () {
            Route::post('/projects/{projectId}/document-types/{typeId}/documents', [DocumentController::class, 'store']);
        });

        Route::middleware('permission:delete_documents')->group(function () {
            Route::delete('/projects/{projectId}/documents/{docId}', [DocumentController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Project Decks
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_decks')->group(function () {
            Route::get('/projects/{projectId}/decks', [DeckController::class, 'index']);
            Route::get('/projects/{projectId}/decks/{deckId}', [DeckController::class, 'show']);
        });

        Route::middleware('permission:create_decks')->group(function () {
            Route::post('/projects/{projectId}/decks', [DeckController::class, 'store']);
        });

        Route::middleware('permission:edit_decks')->group(function () {
            Route::put('/projects/{projectId}/decks/{deckId}', [DeckController::class, 'update']);
        });

        Route::middleware('permission:delete_decks')->group(function () {
            Route::delete('/projects/{projectId}/decks/{deckId}', [DeckController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Project Areas
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_areas')->group(function () {
            Route::get('/projects/{projectId}/areas', [AreaController::class, 'index']);
            Route::get('/projects/{projectId}/areas/{areaId}', [AreaController::class, 'show']);
        });

        Route::middleware('permission:create_areas')->group(function () {
            Route::post('/projects/{projectId}/decks/{deckId}/areas', [AreaController::class, 'store']);
        });

        Route::middleware('permission:edit_areas')->group(function () {
            Route::put('/projects/{projectId}/areas/{areaId}', [AreaController::class, 'update']);
        });

        Route::middleware('permission:delete_areas')->group(function () {
            Route::delete('/projects/{projectId}/areas/{areaId}', [AreaController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Project Stages
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_stages')->group(function () {
            Route::get('/projects/{projectId}/areas/{areaId}/stages', [StageController::class, 'index']);
            Route::get('/projects/{projectId}/stages/{stageId}', [StageController::class, 'show']);
        });

        Route::middleware('permission:create_stages')->group(function () {
            Route::post('/projects/{projectId}/areas/{areaId}/stages', [StageController::class, 'store']);
        });

        Route::middleware('permission:edit_stages')->group(function () {
            Route::put('/projects/{projectId}/stages/{stageId}', [StageController::class, 'update']);
            Route::put('/projects/{projectId}/stages/{stageId}/status', [StageController::class, 'updateStatus']);
        });

        Route::middleware('permission:delete_stages')->group(function () {
            Route::delete('/projects/{projectId}/stages/{stageId}', [StageController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Project Members
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:manage_project_members')->group(function () {
            Route::get('/projects/{projectId}/members', [MemberController::class, 'index']);
            Route::post('/projects/{projectId}/members', [MemberController::class, 'store']);
            Route::delete('/projects/{projectId}/members/{userId}', [MemberController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Project Signers
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:manage_project_signers')->group(function () {
            Route::get('/projects/{projectId}/signers', [SignerController::class, 'index']);
            Route::post('/projects/{projectId}/signers', [SignerController::class, 'store']);
            Route::delete('/projects/{projectId}/signers/{userId}', [SignerController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Project Logbook
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view_logbook')->group(function () {
            Route::get('/projects/{projectId}/logbook', [LogbookController::class, 'index']);
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
