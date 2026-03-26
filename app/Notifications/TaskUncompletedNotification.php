<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskUncompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $note,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'TASK_UNCOMPLETED',
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'problem_id' => $this->task->problem_id,
            'member_id' => $this->task->problem->member_id,
            'note' => $this->note,
        ];
    }
}
