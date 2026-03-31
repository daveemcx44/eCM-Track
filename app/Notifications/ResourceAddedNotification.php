<?php

namespace App\Notifications;

use App\Models\Resource;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResourceAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Resource $resource,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'RESOURCE_ADDED',
            'resource_id' => $this->resource->id,
            'survey_name' => $this->resource->survey_name,
            'task_id' => $this->resource->task_id,
            'submitted_by' => $this->resource->submitted_by,
            'submitted_at' => $this->resource->submitted_at->toISOString(),
        ];
    }
}
