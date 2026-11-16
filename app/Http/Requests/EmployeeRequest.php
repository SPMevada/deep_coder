<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');
        $employee = $this->route('id');

        return [
            'employee_code' => [
                'nullable', 'string', 'max:30',
                Rule::unique('users', 'employee_code')->ignore($employee),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($employee),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'password' => [
                $isCreate ? 'required' : 'nullable',
                'string', 'min:8', 'confirmed',
            ],
            'role_id' => ['required', 'exists:roles,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'designation' => ['required', 'string', 'max:255'],
            'joined_at' => ['required', 'date'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'employee_code.unique' => 'This employee code is already in use.',
            'password.required' => 'Password is required for a new employee.',
        ];
    }
}
