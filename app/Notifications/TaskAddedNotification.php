<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAddedNotification extends Notification
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
            'event' => 'TASK_ADDED',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'problem_id' => $this->task->problem_id,
            'member_id' => $this->task->problem->member_id,
            'submitted_by' => $this->task->submitted_by,
        ];
    }
}
