<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('booked_ticket_id');
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->enum('commission_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('base_amount_paid', 12, 2)->default(0); // Amount agent paid (excluding commission)
            $table->decimal('total_commission_earned', 12, 2)->default(0); // Commission earned by agent
            $table->decimal('passenger_amount_charged', 12, 2)->default(0); // Total amount charged to passenger
            $table->enum('booking_status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('commission_paid_at')->nullable();
            $table->json('commission_details')->nullable(); // Store commission calculation details
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('booked_ticket_id')->references('id')->on('booked_tickets')->onDelete('cascade');

            // Indexes
            $table->index(['agent_id', 'created_at']);
            $table->index('booking_status');
            $table->index('payment_status');
            $table->unique('booked_ticket_id'); // One ticket can only have one agent booking
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_bookings');
    }
};