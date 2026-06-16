<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_part_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('part_name');
            $table->string('bus_info')->nullable();
            $table->enum('priority', [
                'low',
                'medium',
                'high',
                'urgent'
            ])->default('medium');

            $table->text('reason');

            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_part_requests');
    }
};
