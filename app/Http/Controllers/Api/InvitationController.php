<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Http\Requests\Auth\SendInvitationRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationSentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class InvitationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $invitations = Invitation::with('invitedBy')
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return InvitationResource::collection($invitations);
    }

    public function store(SendInvitationRequest $request): JsonResponse
    {
        $invitation = Invitation::create([
            'email' => $request->email,
            'role' => $request->role,
            'invited_by' => $request->user()->id,
        ]);

        $invitation->load('invitedBy');

        Notification::route('mail', $request->email)
            ->notify(new InvitationSentNotification($invitation));

        return response()->json(new InvitationResource($invitation), 201);
    }

    public function show(string $token): JsonResponse
    {
        $invitation = Invitation::with('invitedBy')
            ->where('token', $token)
            ->firstOrFail();

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'InviteAction',
            'identifier' => $invitation->id,
            'recipient' => [
                '@type' => 'Person',
                'email' => $invitation->email,
            ],
            'role' => $invitation->role,
            'actionStatus' => $invitation->isPending() ? 'PotentialActionStatus' : 'FailedActionStatus',
            'isValid' => $invitation->isPending(),
            'isExpired' => $invitation->isExpired(),
            'expires' => $invitation->expires_at->toIso8601String(),
            'invitedBy' => $invitation->invitedBy->name,
        ]);
    }

    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        $invitation = Invitation::where('token', $request->token)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'This invitation has expired.',
            ], 400);
        }

        $user = DB::transaction(function () use ($request, $invitation) {
            $user = User::create([
                'name' => $request->name,
                'email' => $invitation->email,
                'password' => $request->password,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($invitation->role);
            $invitation->markAsAccepted($user);

            return $user;
        });

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json(new AuthResource($user, $token), 201);
    }

    public function decline(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'exists:invitations,token'],
        ]);

        $invitation = Invitation::where('token', $request->token)
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->markAsDeclined();

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'RejectAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Invitation declined successfully.',
        ]);
    }

    public function resend(int $id, Request $request): JsonResponse
    {
        $invitation = Invitation::findOrFail($id);

        // Allow resending pending invitations (including expired ones)
        if ($invitation->status !== 'pending') {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Can only resend pending invitations.',
            ], 400);
        }

        $invitation->update([
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationSentNotification($invitation));

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'SendAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Invitation resent successfully.',
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $invitation = Invitation::findOrFail($id);

        if ($invitation->status !== 'pending') {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Can only cancel pending invitations.',
            ], 400);
        }

        $invitation->update(['status' => 'expired']);

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'CancelAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Invitation cancelled successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Public Methods (no tenant middleware - tenant extracted from token)
    |--------------------------------------------------------------------------
    */

    public function showByToken(string $token): JsonResponse
    {
        $invitation = Invitation::findByToken($token);

        if (!$invitation) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Invitation not found.',
            ], 404);
        }

        $invitation->load('invitedBy');

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'InviteAction',
            'identifier' => $invitation->id,
            'recipient' => [
                '@type' => 'Person',
                'email' => $invitation->email,
            ],
            'role' => $invitation->role,
            'actionStatus' => $invitation->isPending() ? 'PotentialActionStatus' : 'FailedActionStatus',
            'isValid' => $invitation->isPending(),
            'isExpired' => $invitation->isExpired(),
            'expires' => $invitation->expires_at->toIso8601String(),
            'invitedBy' => $invitation->invitedBy->name,
        ]);
    }

    public function acceptPublic(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $invitation = Invitation::findByToken($request->token);

        if (!$invitation) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Invitation not found.',
            ], 404);
        }

        if ($invitation->status !== 'pending') {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'This invitation is no longer valid.',
            ], 400);
        }

        if ($invitation->isExpired()) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'This invitation has expired.',
            ], 400);
        }

        $user = DB::transaction(function () use ($request, $invitation) {
            $user = User::create([
                'name' => $request->name,
                'email' => $invitation->email,
                'password' => $request->password,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($invitation->role);
            $invitation->markAsAccepted($user);

            return $user;
        });

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json(new AuthResource($user, $token), 201);
    }

    public function declinePublic(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $invitation = Invitation::findByToken($request->token);

        if (!$invitation) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'Invitation not found.',
            ], 404);
        }

        if ($invitation->status !== 'pending') {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'This invitation is no longer valid.',
            ], 400);
        }

        $invitation->markAsDeclined();

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'RejectAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Invitation declined successfully.',
        ]);
    }
}
