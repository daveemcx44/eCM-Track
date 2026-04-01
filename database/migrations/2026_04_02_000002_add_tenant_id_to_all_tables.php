<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'members',
            'problems',
            'tasks',
            'resources',
            'notes',
            'state_change_histories',
            'organization_settings',
            'notification_settings',
            'outreach_logs',
            'care_plans',
            'goal_task',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('tenant_id')->default(1)->after('id');
                $blueprint->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'members',
            'problems',
            'tasks',
            'resources',
            'notes',
            'state_change_histories',
            'organization_settings',
            'notification_settings',
            'outreach_logs',
            'care_plans',
            'goal_task',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['tenant_id']);
                $blueprint->dropColumn('tenant_id');
            });
        }
    }
};
