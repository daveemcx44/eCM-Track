<?php

namespace App\Notifications;

use App\Models\Problem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProblemConfirmedNotification extends Notification
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
            'event' => 'PROBLEM_CONFIRMED',
            'problem_id' => $this->problem->id,
            'problem_name' => $this->problem->name,
            'member_id' => $this->problem->member_id,
            'confirmed_by' => $this->problem->confirmed_by,
            'confirmed_at' => $this->problem->confirmed_at->toISOString(),
        ];
    }
}
