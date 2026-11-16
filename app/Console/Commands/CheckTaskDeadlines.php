<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDeadlineAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTaskDeadlines extends Command
{
    protected $signature = 'tasks:check-deadlines';

    protected $description = 'Notify assignees about upcoming and overdue task deadlines';

    public function handle(): int
    {
        Task::with('assignees')->where('status', '!=', 'completed')->whereNotNull('due_date')
            ->whereDate('due_date', '<=', today()->addDays(3))->chunkById(100, function ($tasks): void {
                foreach ($tasks as $task) {
                    $type = $task->due_date->lt(today()) ? 'task_overdue' : 'task_upcoming';
                    foreach ($task->assignees as $user) {
                        $inserted = DB::table('task_deadline_alerts')->insertOrIgnore([
                            'task_id' => $task->id, 'user_id' => $user->id,
                            'due_date' => $task->due_date->toDateString(), 'type' => $type,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        if ($inserted) {
                            User::find($user->id)?->notify(new TaskDeadlineAlert($task, $type));
                        }
                    }
                }
            });

        return self::SUCCESS;
    }
}
