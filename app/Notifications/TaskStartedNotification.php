<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskStartedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'TASK_STARTED',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'problem_id' => $this->task->problem_id,
            'member_id' => $this->task->problem->member_id,
            'started_by' => $this->task->started_by,
        ];
    }
}
