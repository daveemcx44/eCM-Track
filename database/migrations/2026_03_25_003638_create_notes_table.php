<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('notable_type');
            $table->unsignedBigInteger('notable_id');
            $table->text('content');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at');

            $table->index(['notable_type', 'notable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
