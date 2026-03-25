<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('state_change_histories', function (Blueprint $table) {
            $table->id();
            $table->string('trackable_type');
            $table->unsignedBigInteger('trackable_id');
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->foreignId('changed_by')->constrained('users');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['trackable_type', 'trackable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('state_change_histories');
    }
};
