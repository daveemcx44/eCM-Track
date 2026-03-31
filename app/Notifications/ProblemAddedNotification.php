<?php

namespace App\Notifications;

use App\Models\Problem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProblemAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Problem $problem,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'PROBLEM_ADDED',
            'problem_id' => $this->problem->id,
            'problem_name' => $this->problem->name,
            'problem_type' => $this->problem->type->value,
            'member_id' => $this->problem->member_id,
            'submitted_by' => $this->problem->submitted_by,
            'submitted_at' => $this->problem->submitted_at->toISOString(),
        ];
    }
}
