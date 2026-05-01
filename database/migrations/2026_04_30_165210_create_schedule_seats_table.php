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
        Schema::create('schedule_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_bus_id')->constrained()->cascadeOnDelete();
            $table->integer('row');
            $table->string('column', 2);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['schedule_bus_id', 'row', 'column']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_seats');
    }
};
