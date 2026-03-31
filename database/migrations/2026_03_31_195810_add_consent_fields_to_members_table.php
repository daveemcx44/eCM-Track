<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('member_consent_status')->nullable()->after('ji_consent_status');
            $table->string('bh_consent_status')->nullable()->after('member_consent_status');
            $table->string('sud_consent_status')->nullable()->after('bh_consent_status');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['member_consent_status', 'bh_consent_status', 'sud_consent_status']);
        });
    }
};
