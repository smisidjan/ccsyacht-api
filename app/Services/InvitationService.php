<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InvitationSentNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class InvitationService
{
    public function list(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return Invitation::with('invitedBy')
            ->when($status, fn($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(string $email, string $role, User $invitedBy): Invitation
    {
        $invitation = Invitation::create([
            'email' => $email,
            'role' => $role,
            'invited_by' => $invitedBy->id,
        ]);

        $invitation->load('invitedBy');

        Notification::route('mail', $email)
            ->notify(new InvitationSentNotification($invitation));

        return $invitation;
    }

    public function findByToken(string $token): ?Invitation
    {
        if (!str_contains($token, ':')) {
            return null;
        }

        [$tenantSlug, $actualToken] = explode(':', $token, 2);

        $tenant = Tenant::where('slug', $tenantSlug)
            ->where('active', true)
            ->first();

        if (!$tenant) {
            return null;
        }

        tenancy()->initialize($tenant);

        return Invitation::where('token', $token)->first();
    }

    public function accept(Invitation $invitation, string $name, string $password): array
    {
        $user = DB::transaction(function () use ($invitation, $name, $password) {
            $user = User::create([
                'name' => $name,
                'email' => $invitation->email,
                'password' => $password,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($invitation->role);
            $invitation->markAsAccepted($user);

            return $user;
        });

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function decline(Invitation $invitation): void
    {
        $invitation->markAsDeclined();
    }

    public function resend(Invitation $invitation): void
    {
        $invitation->update([
            'expires_at' => now()->addDays(7),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new InvitationSentNotification($invitation));
    }

    public function cancel(Invitation $invitation): void
    {
        $invitation->update(['status' => 'expired']);
    }
}
