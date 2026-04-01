<?php

namespace App\Models;

use App\Enums\NotificationEventType;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'event_type', 'enabled'];

    protected function casts(): array
    {
        return [
            'event_type' => NotificationEventType::class,
            'enabled' => 'boolean',
        ];
    }

    /**
     * Check if a notification event type is enabled.
     * Defaults to true if no setting exists (fail-open).
     */
    public static function isEnabled(NotificationEventType $type): bool
    {
        $setting = static::where('event_type', $type->value)->first();

        return $setting ? $setting->enabled : true;
    }
}
