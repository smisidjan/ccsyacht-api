<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ProcessRegistrationRequest;
use App\Http\Requests\Auth\RequestRegistrationRequest;
use App\Http\Resources\RegistrationRequestResource;
use App\Models\RegistrationRequest;
use App\Services\RegistrationRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegistrationRequestController extends Controller
{
    public function __construct(
        private RegistrationRequestService $registrationRequestService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $registrationRequests = $this->registrationRequestService->list($request->status);

        return RegistrationRequestResource::collection($registrationRequests);
    }

    public function store(RequestRegistrationRequest $request): JsonResponse
    {
        $registrationRequest = $this->registrationRequestService->create(
            $request->name,
            $request->email,
            $request->password,
            $request->message
        );

        return $this->resourceResponse(new RegistrationRequestResource($registrationRequest), 201);
    }

    public function show(int $id): JsonResponse
    {
        $registrationRequest = RegistrationRequest::with('processedBy')->findOrFail($id);

        return $this->resourceResponse(new RegistrationRequestResource($registrationRequest));
    }

    public function process(int $id, ProcessRegistrationRequest $request): JsonResponse
    {
        $registrationRequest = RegistrationRequest::findOrFail($id);

        if (!$registrationRequest->isPending()) {
            return $this->errorResponse('This registration request has already been processed.');
        }

        if ($request->action === 'approve') {
            $this->registrationRequestService->approve(
                $registrationRequest,
                $request->user(),
                $request->role,
                $request->employment_type,
                $request->home_organization_id,
                $request->home_organization_name
            );

            return $this->successWithResult(
                'ApproveAction',
                'Registration request approved successfully.',
                new RegistrationRequestResource($registrationRequest->fresh('processedBy'))
            );
        }

        $this->registrationRequestService->reject(
            $registrationRequest,
            $request->user(),
            $request->rejection_reason
        );

        return $this->successWithResult(
            'RejectAction',
            'Registration request rejected.',
            new RegistrationRequestResource($registrationRequest->fresh('processedBy'))
        );
    }
}
