<?php

namespace App\Services;

use App\Models\RegistrationRequest;
use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\RegistrationApprovedNotification;
use App\Notifications\RegistrationRejectedNotification;
use App\Notifications\RegistrationRequestSubmittedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RegistrationRequestService
{
    public function list(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return RegistrationRequest::with('processedBy')
            ->when($status, fn($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(string $name, string $email, string $password, ?string $message = null): RegistrationRequest
    {
        $registrationRequest = RegistrationRequest::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'message' => $message,
            'status' => 'pending',
        ]);

        $admins = User::role(['admin', 'main user', 'invitation manager'])->get();
        Notification::send($admins, new RegistrationRequestSubmittedNotification($registrationRequest));

        return $registrationRequest;
    }

    public function approve(RegistrationRequest $registrationRequest, User $processedBy, string $role): User
    {
        $user = DB::transaction(function () use ($registrationRequest, $processedBy, $role) {
            $user = new User([
                'name' => $registrationRequest->name,
                'email' => $registrationRequest->email,
                'email_verified_at' => now(),
            ]);
            $user->password = $registrationRequest->password;
            $user->save();

            $user->assignRole($role);
            $registrationRequest->approve($processedBy, $user);

            // Add to central TenantUser table for lookup
            TenantUser::updateOrCreate(
                ['email' => $user->email, 'tenant_id' => tenant()->id],
                ['user_id' => $user->id]
            );

            return $user;
        });

        Notification::route('mail', $registrationRequest->email)
            ->notify(new RegistrationApprovedNotification($registrationRequest));

        return $user;
    }

    public function reject(RegistrationRequest $registrationRequest, User $processedBy, ?string $reason = null): void
    {
        $registrationRequest->reject($processedBy, $reason);

        Notification::route('mail', $registrationRequest->email)
            ->notify(new RegistrationRejectedNotification($registrationRequest));
    }
}
