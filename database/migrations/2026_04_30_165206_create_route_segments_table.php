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
            $table->foreignId('stop_id')->constrained('stops')->restrictOnDelete();
            $table->integer('sequence');       // 1 = titik awal, terakhir = tujuan akhir
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
