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
        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_trip_id')->constrained()->cascadeOnDelete();
            $table->integer('duration_minutes')->default(0);
            $table->json('departure_times');
            $table->json('days_of_week');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_templates');
    }
};
