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
        Schema::create('spare_part_in_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_part_in_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_part_id')->constrained()->restrictOnDelete();
            $table->integer('quantity');
            $table->decimal('price_per_unit', 12, 2);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_part_in_items');
    }
};
