<?php

namespace App\Services\CareManagement;

use App\Enums\NotificationEventType;
use App\Models\Member;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification to the lead care manager if the event type is enabled.
     */
    public function notifyLeadCareManager(
        Member $member,
        NotificationEventType $eventType,
        Notification $notification,
    ): void {
        // Check if this event type is enabled
        if (!NotificationSetting::isEnabled($eventType)) {
            return;
        }

        // Resolve the lead care manager
        if (!$member->lead_care_manager) {
            Log::warning("No lead care manager assigned for member {$member->id}. Notification for {$eventType->value} not sent.");
            return;
        }

        $leadCm = User::find($member->lead_care_manager);

        if (!$leadCm) {
            Log::warning("Lead care manager (ID: {$member->lead_care_manager}) not found for member {$member->id}. Notification for {$eventType->value} not sent.");
            return;
        }

        $leadCm->notify($notification);
    }
}
