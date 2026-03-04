<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'main user', 'invitation manager']);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'role' => [
                'required_if:action,approve',
                'string',
                Rule::in(['admin', 'main user', 'invitation manager', 'user', 'surveyor', 'painter', 'owner representative']),
            ],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
