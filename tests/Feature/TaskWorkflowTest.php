<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Project;
use App\Models\ProjectAllocation;
use App\Models\Task;
use App\Models\User;
use App\Services\AllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_assigned_task_and_notify_employee(): void
    {
        [$manager, $employee, $project] = $this->projectFixture();
        $this->actingAs($manager, 'sanctum')->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Prepare release notes',
            'start_date' => today()->toDateString(),
            'due_date' => today()->addDays(2)->toDateString(),
            'priority' => 'high',
            'status' => 'pending',
            'assignees' => [$employee->id],
        ])->assertCreated();

        $this->assertDatabaseHas('tasks', ['project_id' => $project->id, 'title' => 'Prepare release notes']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $employee->id, 'type' => 'App\\Notifications\\TaskAssigned']);
    }

    public function test_project_progress_uses_completed_tasks_and_overdue_excludes_completed_tasks(): void
    {
        [$manager, , $project] = $this->projectFixture();
        $completed = Task::create(['project_id' => $project->id, 'created_by' => $manager->id, 'title' => 'Done', 'status' => 'completed', 'priority' => 'low', 'due_date' => today()->subDay()]);
        $pending = Task::create(['project_id' => $project->id, 'created_by' => $manager->id, 'title' => 'Late', 'status' => 'pending', 'priority' => 'medium', 'due_date' => today()->subDay()]);

        $this->assertSame(50, $project->fresh()->progress_percentage);
        $this->assertFalse($completed->is_overdue);
        $this->assertTrue($pending->is_overdue);
        $this->assertSame(0, Project::create([
            'name' => 'Empty project', 'code' => 'EMPTY-1', 'department_id' => $project->department_id,
            'manager_id' => $manager->id, 'created_by' => $manager->id, 'start_date' => today(),
            'deadline' => today()->addDays(1), 'status' => 'planning', 'priority' => 'low',
        ])->progress_percentage);
    }

    public function test_employee_cannot_view_an_unassigned_project_through_api(): void
    {
        [, $employee, $project] = $this->projectFixture(false);

        $this->actingAs($employee, 'sanctum')->getJson('/api/projects/'.$project->id)->assertForbidden();
    }

    public function test_task_creation_rejects_completed_projects(): void
    {
        [$manager, $employee, $project] = $this->projectFixture();
        $project->update(['status' => 'completed']);

        $this->actingAs($manager, 'sanctum')->postJson('/api/tasks', [
            'project_id' => $project->id,
            'title' => 'Cannot add task',
            'start_date' => today()->toDateString(),
            'due_date' => today()->addDay()->toDateString(),
            'priority' => 'medium',
            'status' => 'pending',
            'assignees' => [$employee->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('project_id');

        $this->assertDatabaseMissing('tasks', ['title' => 'Cannot add task']);
    }

    public function test_assigned_employee_can_start_and_complete_a_task_without_edit_permission(): void
    {
        [$manager, $employee, $project] = $this->projectFixture();
        $task = Task::create([
            'project_id' => $project->id,
            'created_by' => $manager->id,
            'title' => 'Implement feature',
            'status' => 'pending',
            'priority' => 'medium',
        ]);
        $task->assignees()->attach($employee->id);

        $this->actingAs($employee, 'sanctum')->get('/tasks')->assertOk()
            ->assertSee('Change task status')
            ->assertSee('Pending')
            ->assertSee('In progress')
            ->assertSee('Completed');
        $this->post('/tasks/'.$task->id.'/status', ['status' => 'in_progress'])
            ->assertRedirect(route('tasks.show', $task));
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'in_progress']);
        $this->patchJson('/api/tasks/'.$task->id.'/status', ['status' => 'completed'])->assertOk();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'completed']);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $manager->id,
            'type' => 'App\\Notifications\\TaskStatusChanged',
        ]);
        $this->patchJson('/api/tasks/'.$task->id.'/status', ['status' => 'pending'])->assertOk();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'pending']);
        $this->patchJson('/api/tasks/'.$task->id, ['status' => 'pending'])->assertForbidden();

        $this->actingAs($manager, 'sanctum')->patchJson('/api/tasks/'.$task->id.'/status', ['status' => 'completed'])->assertForbidden();
    }

    public function test_allocation_capacity_excludes_the_allocation_being_updated(): void
    {
        [, $employee, $project] = $this->projectFixture();
        $allocation = ProjectAllocation::query()->where('project_id', $project->id)->where('user_id', $employee->id)->firstOrFail();
        $service = app(AllocationService::class);
        $start = Carbon::today();
        $end = Carbon::today()->addDays(10);

        $this->assertNotNull($service->validate($project, $employee, $start, $end, 51));
        $this->assertNull($service->validate($project, $employee, $start, $end, 100, $allocation->id));
    }

    public function test_manager_can_view_reports_and_csv_export_escapes_spreadsheet_formulas(): void
    {
        [$manager, , $project] = $this->projectFixture();
        Task::create([
            'project_id' => $project->id, 'created_by' => $manager->id,
            'title' => '=HYPERLINK("https://example.invalid")', 'status' => 'pending', 'priority' => 'high',
        ]);

        $this->actingAs($manager, 'sanctum')->get('/reports')->assertOk()->assertSee('Employee workload');
        $response = $this->actingAs($manager, 'sanctum')->get('/reports/export?type=tasks');
        $response->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());

        $workloadResponse = $this->get('/reports/export?type=allocations');
        $workloadResponse->assertOk();
        $this->assertStringContainsString('50', $workloadResponse->streamedContent());
    }

    /** @return array{User, User, Project} */
    private function projectFixture(bool $assignEmployee = true): array
    {
        $managerRoleId = DB::table('roles')->insertGetId(['name' => 'Manager', 'slug' => 'manager', 'created_at' => now(), 'updated_at' => now()]);
        $employeeRoleId = DB::table('roles')->insertGetId(['name' => 'Employee', 'slug' => 'employee', 'created_at' => now(), 'updated_at' => now()]);
        $manager = User::factory()->create(['role_id' => $managerRoleId]);
        $employee = User::factory()->create(['role_id' => $employeeRoleId]);
        $department = Department::create(['name' => 'Engineering']);
        $project = Project::create([
            'name' => 'Release project', 'code' => 'PRJ-TEST', 'department_id' => $department->id,
            'manager_id' => $manager->id, 'created_by' => $manager->id,
            'start_date' => today()->subDay(), 'deadline' => today()->addDays(10),
            'status' => 'in_progress', 'priority' => 'high', 'progress' => 0,
        ]);
        if ($assignEmployee) {
            DB::table('project_allocations')->insert([
                'project_id' => $project->id, 'user_id' => $employee->id, 'allocation_percentage' => 50,
                'start_date' => today()->toDateString(), 'end_date' => today()->addDays(10)->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return [$manager, $employee, $project];
    }
}
