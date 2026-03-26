<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskCompletedNotification extends Notification
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
            'event' => 'TASK_COMPLETED',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'problem_id' => $this->task->problem_id,
            'member_id' => $this->task->problem->member_id,
            'completion_type' => $this->task->completion_type?->value,
            'completed_by' => $this->task->completed_by,
        ];
    }
}
