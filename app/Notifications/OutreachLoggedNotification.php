<?php

namespace App\Notifications;

use App\Models\OutreachLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OutreachLoggedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public OutreachLog $outreachLog,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'OUTREACH_LOGGED',
            'outreach_log_id' => $this->outreachLog->id,
            'member_id' => $this->outreachLog->member_id,
            'method' => $this->outreachLog->method->value,
            'outcome' => $this->outreachLog->outcome->value,
            'staff_id' => $this->outreachLog->staff_id,
        ];
    }
}
