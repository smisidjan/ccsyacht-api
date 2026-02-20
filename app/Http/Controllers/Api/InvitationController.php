<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Http\Requests\Auth\SendInvitationRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvitationController extends Controller
{
    public function __construct(
        private InvitationService $invitationService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $invitations = $this->invitationService->list($request->status);

        return InvitationResource::collection($invitations);
    }

    public function store(SendInvitationRequest $request): JsonResponse
    {
        $invitation = $this->invitationService->create(
            $request->email,
            $request->role,
            $request->user()
        );

        return $this->resourceResponse(new InvitationResource($invitation), 201);
    }

    public function show(string $token): JsonResponse
    {
        $invitation = Invitation::with('invitedBy')
            ->where('token', $token)
            ->firstOrFail();

        return $this->resourceResponse(InvitationResource::detailed($invitation));
    }

    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        $invitation = Invitation::where('token', $request->token)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return $this->errorResponse('This invitation has expired.');
        }

        $result = $this->invitationService->accept($invitation, $request->name, $request->password);

        return $this->resourceResponse(new AuthResource($result['user'], $result['token']), 201);
    }

    public function decline(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'exists:invitations,token'],
        ]);

        $invitation = Invitation::where('token', $request->token)
            ->where('status', 'pending')
            ->firstOrFail();

        $this->invitationService->decline($invitation);

        return $this->successResponse('RejectAction', 'Invitation declined successfully.');
    }

    public function resend(int $id, Request $request): JsonResponse
    {
        $invitation = Invitation::findOrFail($id);

        if ($invitation->status !== 'pending') {
            return $this->errorResponse('Can only resend pending invitations.');
        }

        $this->invitationService->resend($invitation);

        return $this->successResponse('SendAction', 'Invitation resent successfully.');
    }

    public function cancel(int $id): JsonResponse
    {
        $invitation = Invitation::findOrFail($id);

        if ($invitation->status !== 'pending') {
            return $this->errorResponse('Can only cancel pending invitations.');
        }

        $this->invitationService->cancel($invitation);

        return $this->successResponse('CancelAction', 'Invitation cancelled successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Public Methods (no tenant middleware - tenant extracted from token)
    |--------------------------------------------------------------------------
    */

    public function showByToken(string $token): JsonResponse
    {
        $invitation = $this->invitationService->findByToken($token);

        if (!$invitation) {
            return $this->errorResponse('Invitation not found.', 404);
        }

        $invitation->load('invitedBy');

        return $this->resourceResponse(InvitationResource::detailed($invitation));
    }

    public function acceptPublic(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $invitation = $this->invitationService->findByToken($request->token);

        if (!$invitation) {
            return $this->errorResponse('Invitation not found.', 404);
        }

        if ($invitation->status !== 'pending') {
            return $this->errorResponse('This invitation is no longer valid.');
        }

        if ($invitation->isExpired()) {
            return $this->errorResponse('This invitation has expired.');
        }

        $result = $this->invitationService->accept($invitation, $request->name, $request->password);

        return $this->resourceResponse(new AuthResource($result['user'], $result['token']), 201);
    }

    public function declinePublic(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $invitation = $this->invitationService->findByToken($request->token);

        if (!$invitation) {
            return $this->errorResponse('Invitation not found.', 404);
        }

        if ($invitation->status !== 'pending') {
            return $this->errorResponse('This invitation is no longer valid.');
        }

        $this->invitationService->decline($invitation);

        return $this->successResponse('RejectAction', 'Invitation declined successfully.');
    }
}
