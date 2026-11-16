<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
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
        $staffRoleIds = Role::whereIn('slug', ['admin', 'manager'])->pluck('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => [
                'required',
                Rule::exists('users', 'id')->whereIn('role_id', $staffRoleIds),
            ],
            'start_date' => ['required', 'date'],
            // Business rule: deadline cannot be before start date
            'deadline' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'priority' => ['required', Rule::in(Project::PRIORITIES)],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
