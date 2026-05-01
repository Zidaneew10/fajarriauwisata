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
        Schema::create('bus_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_trip_id')->constrained()->cascadeOnDelete();
            $table->enum('class_type', ['Sleeper', 'SE 2-1', 'Executive']);
            $table->decimal('price', 12, 2);
            $table->integer('capacity');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_classes');
    }
};
