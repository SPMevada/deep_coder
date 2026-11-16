<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskDeadlineAlert extends Notification
{
    public function __construct(public Task $task, public string $alertType) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->alertType,
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'due_date' => $this->task->due_date?->toDateString(),
            'message' => $this->alertType === 'task_overdue' ? 'This task is overdue.' : 'This task is due soon.',
        ];
    }
}
