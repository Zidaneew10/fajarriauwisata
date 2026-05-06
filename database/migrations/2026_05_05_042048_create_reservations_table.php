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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_code')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->string('destination');               // tujuan perjalanan
            $table->date('departure_date');
            $table->date('return_date')->nullable();     // null jika one way
            $table->integer('passenger_count');
            $table->text('notes')->nullable();
            $table->enum('status', [
                'pending',      // baru masuk
                'confirmed',    // sudah dikonfirmasi admin
                'in_progress',  // sedang berjalan
                'completed',    // selesai
                'cancelled',    // dibatalkan
            ])->default('pending');
            $table->decimal('total_price', 12, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'dp', 'paid'])->default('unpaid');
            $table->decimal('dp_amount', 12, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // admin yang input
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
