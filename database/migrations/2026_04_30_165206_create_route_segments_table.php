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
        Schema::create('route_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('terminal_id')->constrained()->restrictOnDelete();
            $table->integer('sequence');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_segments');
    }
};
