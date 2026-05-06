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
        Schema::create('spare_parts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();          // kode sparepart
            $table->string('name');                    // nama sparepart
            $table->string('unit');                    // satuan: pcs, liter, set, dll
            $table->integer('stock')->default(0);      // stok saat ini
            $table->integer('safety_stock');           // batas aman minimum
            $table->integer('rop')->default(0);;                    // reorder point — otomatis dihitung
            $table->decimal('lead_time', 5, 2)->default(1); // rata-rata waktu tunggu (hari)
            $table->decimal('avg_daily_usage', 8, 2)->default(0); // rata-rata pemakaian/hari
            $table->decimal('price', 12, 2)->default(0); // harga satuan
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_parts');
    }
};
