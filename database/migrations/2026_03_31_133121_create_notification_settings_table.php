<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        // Seed all event types as enabled by default
        $eventTypes = [
            'problem_added', 'problem_confirmed', 'problem_unconfirmed',
            'problem_resolved', 'problem_unresolved', 'task_added',
            'task_started', 'task_completed', 'task_uncompleted',
            'resource_added', 'note_added', 'outreach_logged',
        ];

        foreach ($eventTypes as $type) {
            \App\Models\NotificationSetting::create([
                'event_type' => $type,
                'enabled' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
