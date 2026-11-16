<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');
        if ($task instanceof Task) {
            return $this->user()?->can('update', $task) ?? false;
        }

        $project = Project::find($this->input('project_id'));

        return ($this->user()?->can('create', Task::class) ?? false)
            && (! $project || ($this->user()?->can('update', $project) ?? false));
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'status' => [Rule::requiredIf(! $this->route('task')), 'nullable', Rule::in(Task::STATUSES)],
            'assignees' => ['nullable', 'array'],
            'assignees.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $project = Project::find($this->integer('project_id'));
            $existingTask = $this->route('task');
            if ($project?->status === 'completed' && (! $existingTask || $existingTask->project_id !== $project->id)) {
                $validator->errors()->add('project_id', 'Tasks cannot be added to a completed project.');

                return;
            }

            if ($this->filled('start_date') && $project?->start_date && $this->date('start_date')?->lt($project->start_date)) {
                $validator->errors()->add('start_date', 'Task start date cannot precede the project start date.');
            }

            if ($this->filled('due_date') && $project?->deadline && $this->date('due_date')?->gt($project->deadline)) {
                $validator->errors()->add('due_date', 'Task due date cannot exceed the project deadline.');
            }

            $assignees = (array) $this->input('assignees', []);
            if ($assignees !== [] && Project::find($this->integer('project_id'))?->employees()->whereIn('users.id', $assignees)->where('users.is_active', true)->whereHas('role', fn ($query) => $query->where('slug', 'employee'))->count() !== count(array_unique($assignees))) {
                $validator->errors()->add('assignees', 'Every assignee must be allocated to the selected project.');
            }
        }];
    }
}
