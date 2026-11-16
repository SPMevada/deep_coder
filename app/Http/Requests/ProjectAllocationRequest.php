<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\AllocationService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('post')) {
            $project = Project::find($this->input('project_id'));

            // Missing project -> let validation return the 422
            return ! $project || $this->user()->can('update', $project);
        }

        return $this->user()->can('update', $this->route('allocation'));
    }

    public function rules(): array
    {
        $rules = [
            'role_in_project' => ['nullable', 'string', 'max:50'],
            'allocation_percentage' => ['required', 'integer', 'between:1,100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];

        // Project and employee are chosen on create only
        if ($this->isMethod('post')) {
            $allocatableRoleIds = Role::whereIn('slug', ['employee', 'manager'])->pluck('id');

            $rules['project_id'] = ['required', 'exists:projects,id'];
            $rules['user_id'] = [
                'required',
                Rule::exists('users', 'id')->where('is_active', true)->whereIn('role_id', $allocatableRoleIds),
            ];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return; // basic rules failed, skip business checks
            }

            $allocation = $this->route('allocation'); // null on create

            $project = $allocation?->project ?? Project::find($this->project_id);
            $employee = $allocation?->user ?? User::find($this->user_id);

            $allocationService = new AllocationService;
            $error = $allocationService->validate(
                $project,
                $employee,
                Carbon::parse($this->start_date)->startOfDay(),
                $this->end_date ? Carbon::parse($this->end_date)->startOfDay() : null,
                (int) $this->allocation_percentage,
                $allocation?->id
            );

            if ($error) {
                $validator->errors()->add('allocation_percentage', $error);
            }
        });
    }
}
