<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

class ProcessRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('process_registrations') ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'role' => [
                'required_if:action,approve',
                'string',
                Rule::in(Role::pluck('name')->toArray()),
            ],
            'employment_type' => [
                'required_if:action,approve',
                'string',
                Rule::in(Role::pluck('type')->unique()->values()->toArray()),
            ],
            'home_organization_id' => ['nullable', 'string', 'uuid'],
            'home_organization_name' => ['nullable', 'string', 'max:255'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('action') !== 'approve') {
                return;
            }

            // Guests must have a home organization
            if ($this->input('employment_type') === 'guest') {
                if (empty($this->input('home_organization_id')) && empty($this->input('home_organization_name'))) {
                    $validator->errors()->add(
                        'home_organization_name',
                        'A guest must have a home organization (ID or name).'
                    );
                }
            }
        });
    }
}
