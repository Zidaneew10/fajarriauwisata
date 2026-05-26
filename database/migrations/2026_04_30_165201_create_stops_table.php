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
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Bundaran Panam, Terminal Akap
            $table->string('city');                          // Pekanbaru, Dumai
            $table->string('address')->nullable();           // Depan SPBU Shell, Jl. HR Soebrantas
            $table->enum('type', ['terminal', 'titik_jalan'])->default('terminal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terminals');
    }
};
