<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperatorBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('operator_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('operators')->onDelete('cascade');
            $table->foreignId('operator_bus_id')->constrained('operator_buses')->onDelete('cascade');
            $table->foreignId('operator_route_id')->constrained('operator_routes')->onDelete('cascade');
            $table->foreignId('bus_schedule_id')->nullable()->constrained('bus_schedules')->onDelete('cascade');

            // Seat information
            $table->json('blocked_seats'); // Array of seat numbers/IDs
            $table->integer('total_seats_blocked');

            // Date information
            $table->date('journey_date'); // Single date
            $table->date('journey_date_end')->nullable(); // For date range (optional)
            $table->boolean('is_date_range')->default(false);

            // Booking details
            $table->string('booking_reason')->nullable(); // Why blocking seats
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'cancelled', 'expired'])->default('active');

            // No payment fields - operators don't pay for their own bookings
            $table->decimal('blocked_amount', 10, 2)->default(0); // Always 0 for operator bookings

            $table->timestamps();

            // Indexes
            $table->index(['operator_id', 'journey_date']);
            $table->index(['operator_bus_id', 'journey_date']);
            $table->index(['status', 'journey_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operator_bookings');
    }
}
