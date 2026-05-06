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
        Schema::create('spare_part_ins', function (Blueprint $table) {
            $table->id();
           $table->foreignId('spare_part_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_number')->unique(); // nomor referensi pembelian
            $table->integer('quantity')->nullable();                // jumlah masuk
            $table->decimal('price_per_unit', 12, 2)->nullable();    // harga per unit saat pembelian
            $table->string('supplier')->nullable();       // nama supplier
            $table->date('received_at');                  // tanggal diterima
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_part_ins');
    }
};
