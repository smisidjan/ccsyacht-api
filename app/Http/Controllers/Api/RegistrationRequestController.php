<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ProcessRegistrationRequest;
use App\Http\Requests\Auth\RequestRegistrationRequest;
use App\Http\Resources\RegistrationRequestResource;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Notifications\RegistrationApprovedNotification;
use App\Notifications\RegistrationRejectedNotification;
use App\Notifications\RegistrationRequestSubmittedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RegistrationRequestController extends Controller
{
    /**
     * List all registration requests.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $registrationRequests = RegistrationRequest::with('processedBy')
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return RegistrationRequestResource::collection($registrationRequests);
    }

    /**
     * Submit a new registration request.
     *
     * Creates a registration request and notifies admins, main users, and invitation managers.
     *
     * @param RequestRegistrationRequest $request
     * @return JsonResponse
     */
    public function store(RequestRegistrationRequest $request): JsonResponse
    {
        $registrationRequest = RegistrationRequest::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'message' => $request->message,
        ]);

        $admins = User::role(['admin', 'main user', 'invitation manager'])->get();
        Notification::send($admins, new RegistrationRequestSubmittedNotification($registrationRequest));

        return response()->json(new RegistrationRequestResource($registrationRequest), 201);
    }

    /**
     * Show a specific registration request.
     */
    public function show(int $id): JsonResponse
    {
        $registrationRequest = RegistrationRequest::with('processedBy')->findOrFail($id);

        return response()->json(new RegistrationRequestResource($registrationRequest));
    }

    /**
     * Process a registration request (approve or reject).
     */
    public function process(int $id, ProcessRegistrationRequest $request): JsonResponse
    {
        $registrationRequest = RegistrationRequest::findOrFail($id);
        if (!$registrationRequest->isPending()) {
            return response()->json([
                '@context' => 'https://schema.org',
                '@type' => 'Action',
                'actionStatus' => 'FailedActionStatus',
                'error' => 'This registration request has already been processed.',
            ], 400);
        }

        if ($request->action === 'approve') {
            return $this->approve($registrationRequest, $request);
        }

        return $this->reject($registrationRequest, $request);
    }

    /**
     * Approve a registration request.
     *
     * Creates a new user account with the password provided during registration.
     * Sends an approval notification email.
     *
     * @param RegistrationRequest $registrationRequest
     * @param ProcessRegistrationRequest $request
     * @return JsonResponse
     */
    private function approve(RegistrationRequest $registrationRequest, ProcessRegistrationRequest $request): JsonResponse
    {
        DB::transaction(function () use ($registrationRequest, $request) {
            $user = new User([
                'name' => $registrationRequest->name,
                'email' => $registrationRequest->email,
                'email_verified_at' => now(),
            ]);
            $user->password = $registrationRequest->password;
            $user->save();

            $user->assignRole($request->role);
            $registrationRequest->approve($request->user(), $user);

            return $user;
        });

        Notification::route('mail', $registrationRequest->email)
            ->notify(new RegistrationApprovedNotification($registrationRequest));

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'ApproveAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Registration request approved successfully.',
            'result' => new RegistrationRequestResource($registrationRequest->fresh('processedBy')),
        ]);
    }

    /**
     * Reject a registration request.
     *
     * Marks the request as rejected and sends a rejection notification email.
     *
     * @param RegistrationRequest $registrationRequest
     * @param ProcessRegistrationRequest $request
     * @return JsonResponse
     */
    private function reject(RegistrationRequest $registrationRequest, ProcessRegistrationRequest $request): JsonResponse
    {
        $registrationRequest->reject($request->user(), $request->rejection_reason);

        Notification::route('mail', $registrationRequest->email)
            ->notify(new RegistrationRejectedNotification($registrationRequest));

        return response()->json([
            '@context' => 'https://schema.org',
            '@type' => 'RejectAction',
            'actionStatus' => 'CompletedActionStatus',
            'description' => 'Registration request rejected.',
            'result' => new RegistrationRequestResource($registrationRequest->fresh('processedBy')),
        ]);
    }
}
