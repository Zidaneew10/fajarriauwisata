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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->foreignId('schedule_bus_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled'])->default('pending');
            $table->decimal('total_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('midtrans_token')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
