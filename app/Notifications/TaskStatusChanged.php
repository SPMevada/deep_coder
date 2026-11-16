<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\Notification;

class TaskStatusChanged extends Notification
{
    public function __construct(public Task $task, public User $actor, public string $previousStatus) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = str_replace('_', ' ', $this->task->status);

        return [
            'type' => 'task_status_changed',
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'previous_status' => $this->previousStatus,
            'status' => $this->task->status,
            'message' => $this->actor->name.' changed this task from '.str_replace('_', ' ', $this->previousStatus).' to '.$status.'.',
        ];
    }
}
