<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'main user', 'invitation manager']);
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
                Rule::unique('invitations', 'email')->where(function ($query) {
                    return $query->where('status', 'pending');
                }),
            ],
            'role' => [
                'required',
                'string',
                Rule::in(['admin', 'main user', 'invitation manager', 'user', 'yard', 'surveyor', 'painter', 'owner representative']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered or has a pending invitation.',
            'role.in' => 'Invalid role. Must be admin, main user, invitation manager, user, yard, surveyor, painter or owner representative.',
        ];
    }
}
