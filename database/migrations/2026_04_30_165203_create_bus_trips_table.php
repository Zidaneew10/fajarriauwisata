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
        Schema::create('bus_trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number')->unique(); // contoh: PKU-DMI-01
            $table->enum('class_type', ['Ekonomi', 'Executive', 'SE 2-1', 'Sleeper']);
            $table->integer('capacity');             // jumlah kursi, contoh: 45
            $table->decimal('price', 12, 2);         // harga per kursi
            $table->enum('seat_layout', ['2-2', '2-1', '1-1'])->default('2-2');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_trips');
    }
};
