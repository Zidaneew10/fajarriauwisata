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
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->string('bus_code')->unique();        // kode internal
            $table->string('plate_number')->unique();    // nomor polisi
            $table->enum('class_type', ['Sleeper', 'SE 2-1', 'Executive']);
            $table->integer('capacity');
            $table->string('brand')->nullable();         // merek: Scania, Mercedes, dll
            $table->string('model')->nullable();         // tipe: Jetbus 5, dll
            $table->year('year')->nullable();            // tahun
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};
