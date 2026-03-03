<?php

declare(strict_types=1);

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
use InvalidArgumentException;

class InvitationController extends Controller
{
    public function __construct(
        private InvitationService $invitationService
    ) {}

    /**
     * List invitations for the current tenant.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $invitations = $this->invitationService->list($request->status);

        return InvitationResource::collection($invitations);
    }

    /**
     * Create a new invitation (employee or guest).
     */
    public function store(SendInvitationRequest $request): JsonResponse
    {
        try {
            $invitation = $this->invitationService->create(
                email: $request->email,
                role: $request->role ?? $request->role_name ?? 'user',
                invitedBy: $request->user(),
                employmentType: $request->employment_type ?? 'employee',
                homeOrganizationId: $request->home_organization_id,
                homeOrganizationName: $request->home_organization_name,
                namedPosition: $request->named_position,
            );

            return $this->resourceResponse(new InvitationResource($invitation), 201);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Show a specific invitation by ID.
     */
    public function show(string $id): JsonResponse
    {
        $invitation = Invitation::with('invitedBy')
            ->findOrFail($id);

        return $this->resourceResponse(InvitationResource::detailed($invitation));
    }

    /**
     * Accept an invitation (within tenant context).
     */
    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        $invitation = Invitation::where('token', $request->token)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return $this->errorResponse('This invitation has expired.');
        }

        try {
            $result = $this->invitationService->accept($invitation, $request->name, $request->password);

            return $this->resourceResponse(new AuthResource($result['user'], $result['token']), 201);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Decline an invitation.
     */
    public function decline(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'exists:invitations,token'],
        ]);

        $invitation = Invitation::where('token', $request->token)
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            $this->invitationService->decline($invitation);

            return $this->successResponse('RejectAction', 'Invitation declined successfully.');
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Resend an invitation email.
     */
    public function resend(int $id, Request $request): JsonResponse
    {
        $invitation = Invitation::findOrFail($id);

        try {
            $this->invitationService->resend($invitation);

            return $this->successResponse('SendAction', 'Invitation resent successfully.');
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Cancel an invitation.
     */
    public function cancel(int $id): JsonResponse
    {
        $invitation = Invitation::findOrFail($id);

        try {
            $this->invitationService->cancel($invitation);

            return $this->successResponse('CancelAction', 'Invitation cancelled successfully.');
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Public Methods (no tenant middleware - token-based lookup)
    |--------------------------------------------------------------------------
    */

    /**
     * Show invitation details by token (public endpoint).
     */
    public function showByToken(string $token): JsonResponse
    {
        $invitation = $this->invitationService->findByToken($token);

        if (! $invitation) {
            return $this->errorResponse('Invitation not found.', 404);
        }

        return $this->resourceResponse(InvitationResource::detailed($invitation));
    }

    /**
     * Accept invitation by token (public endpoint for new users).
     */
    public function acceptPublic(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $invitation = $this->invitationService->findByToken($request->token);

        if (! $invitation) {
            return $this->errorResponse('Invitation not found.', 404);
        }

        if (! $invitation->isPending()) {
            return $this->errorResponse('This invitation is no longer valid.');
        }

        try {
            $result = $this->invitationService->accept($invitation, $request->name, $request->password);

            return $this->resourceResponse(new AuthResource($result['user'], $result['token']), 201);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Decline invitation by token (public endpoint).
     */
    public function declinePublic(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $invitation = $this->invitationService->findByToken($request->token);

        if (! $invitation) {
            return $this->errorResponse('Invitation not found.', 404);
        }

        if ($invitation->status !== 'pending') {
            return $this->errorResponse('This invitation is no longer valid.');
        }

        try {
            $this->invitationService->decline($invitation);

            return $this->successResponse('RejectAction', 'Invitation declined successfully.');
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
