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
        Schema::table('problems', function (Blueprint $table) {
            $table->boolean('unsupported_problem_flag')->default(false)->after('care_plan_id');
            $table->string('classification')->nullable()->after('unsupported_problem_flag');
            $table->foreignId('classification_by')->nullable()->after('classification');
            $table->timestamp('classification_at')->nullable()->after('classification_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('problems', function (Blueprint $table) {
            $table->dropColumn(['unsupported_problem_flag', 'classification', 'classification_by', 'classification_at']);
        });
    }
};
