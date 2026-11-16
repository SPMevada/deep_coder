<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskStatusChanged;
use Illuminate\Support\Facades\DB;

class TaskStatusService
{
    public function change(Task $task, User $actor, string $status): Task
    {
        [$updatedTask, $previousStatus] = DB::transaction(function () use ($task, $status): array {
            $lockedTask = Task::query()->lockForUpdate()->findOrFail($task->id);
            $previousStatus = $lockedTask->status;

            $lockedTask->update(['status' => $status]);

            return [$lockedTask->fresh(['project', 'creator', 'assignees']), $previousStatus];
        });

        if ($previousStatus !== $status) {
            $recipients = $updatedTask->assignees->push($updatedTask->creator)
                ->filter(fn (?User $recipient): bool => $recipient !== null && $recipient->isNot($actor))
                ->unique('id');

            foreach ($recipients as $recipient) {
                $recipient->notify(new TaskStatusChanged($updatedTask, $actor, $previousStatus));
            }
        }

        return $updatedTask;
    }
}
