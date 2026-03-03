<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\GuestRolePermission;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // Check user's role_name for permission
        return in_array($user->role_name, ['admin', 'main user', 'invitation manager']);
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'role' => [
                'required',
                'string',
                'max:50',
                Rule::in($this->getAllowedRoles()),
            ],
            'employment_type' => [
                'sometimes',
                'string',
                Rule::in(['employee', 'guest']),
            ],
            'home_organization_id' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    // For guests, either home_organization_id or home_organization_name must be provided
                    if ($this->input('employment_type') === 'guest') {
                        if (empty($value) && empty($this->input('home_organization_name'))) {
                            $fail('A guest invitation must include either a home organization ID or name.');
                        }
                    }
                },
            ],
            'home_organization_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'named_position' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateGuestRole($validator);
            $this->validateNoDuplicateInvitation($validator);
            $this->validateNoExistingUser($validator);
        });
    }

    /**
     * Validate that the role is allowed for guests.
     */
    protected function validateGuestRole(Validator $validator): void
    {
        if ($this->input('employment_type') !== 'guest') {
            return;
        }

        $tenantId = tenant()?->id;
        $role = $this->input('role');

        if (! $tenantId || ! $role) {
            return;
        }

        if (! GuestRolePermission::isRoleAllowedForGuest($tenantId, $role)) {
            $allowedRoles = GuestRolePermission::getAllowedRolesForOrganization($tenantId)->implode(', ');
            $validator->errors()->add(
                'role',
                "The role '{$role}' is not allowed for guests. Allowed roles: {$allowedRoles}"
            );
        }
    }

    /**
     * Validate no pending invitation exists for this email.
     */
    protected function validateNoDuplicateInvitation(Validator $validator): void
    {
        $email = $this->input('email');

        if (! $email) {
            return;
        }

        $exists = Invitation::where('email', $email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists();

        if ($exists) {
            $validator->errors()->add(
                'email',
                'A pending invitation already exists for this email.'
            );
        }
    }

    /**
     * Validate user doesn't already exist in this tenant.
     */
    protected function validateNoExistingUser(Validator $validator): void
    {
        $email = $this->input('email');

        if (! $email) {
            return;
        }

        $exists = User::where('email', $email)->exists();

        if ($exists) {
            $validator->errors()->add(
                'email',
                'This user already exists in this organization.'
            );
        }
    }

    /**
     * Get all allowed roles for invitations.
     */
    protected function getAllowedRoles(): array
    {
        return [
            'admin',
            'main user',
            'invitation manager',
            'user',
            'yard',
            'surveyor',
            'painter',
            'owner representative',
            'viewer',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'role.required' => 'Role is required.',
            'role.in' => 'Invalid role selected.',
            'employment_type.in' => 'Employment type must be either "employee" or "guest".',
        ];
    }
}
