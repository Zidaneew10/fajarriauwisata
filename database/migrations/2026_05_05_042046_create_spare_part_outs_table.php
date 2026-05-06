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
        Schema::create('spare_part_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bus_id')->nullable()->constrained()->nullOnDelete(); // ← tambah
            $table->string('reference_number')->unique();
            $table->integer('quantity')->nullable();
            $table->date('used_at');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_part_outs');
    }
};
