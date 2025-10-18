<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperatorBusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('operator_buses', callback: function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id'); // Foreign key to operators table
            $table->unsignedBigInteger('current_route_id')->nullable(); // Current assigned route (can be changed)
            $table->string('bus_number', 20)->unique(); // e.g., MP2921
            $table->string('bus_type'); // e.g., "Non Ac Seater (2+2)", "AC Sleeper 2+1"
            $table->string('service_name')->default('Seat Seller'); // Service provider name
            $table->string('travel_name'); // e.g., "Kalpana Travels Rewa"
            $table->integer('total_seats')->default(0);
            $table->integer('available_seats')->default(0);

            // Pricing structure
            $table->decimal('base_price', 8, 2)->default(0); // Base price for the bus
            $table->decimal('published_price', 8, 2)->default(0); // Published price
            $table->decimal('offered_price', 8, 2)->default(0); // Offered price (after discounts)
            $table->decimal('agent_commission', 8, 2)->default(0); // Commission for agents
            $table->decimal('service_charges', 8, 2)->default(0); // Service charges
            $table->decimal('tds', 8, 2)->default(0); // TDS amount

            // Bus features
            $table->boolean('id_proof_required')->default(false);
            $table->boolean('is_drop_point_mandatory')->default(false);
            $table->boolean('live_tracking_available')->default(false);
            $table->boolean('m_ticket_enabled')->default(true);
            $table->integer('max_seats_per_ticket')->default(6);
            $table->boolean('partial_cancellation_allowed')->default(true);

            // Bus status and metadata
            $table->boolean('status')->default(1); // Active/Inactive
            $table->text('description')->nullable(); // Additional bus details
            $table->json('amenities')->nullable(); // Bus amenities (AC, WiFi, etc.)
            $table->string('fuel_type')->default('Diesel'); // Fuel type
            $table->year('manufacturing_year')->nullable(); // Manufacturing year
            $table->string('insurance_number')->nullable(); // Insurance details
            $table->date('insurance_expiry')->nullable(); // Insurance expiry
            $table->string('permit_number')->nullable(); // Permit number
            $table->date('permit_expiry')->nullable(); // Permit expiry
            $table->string('fitness_certificate')->nullable(); // Fitness certificate
            $table->date('fitness_expiry')->nullable(); // Fitness expiry

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->foreign('current_route_id')->references('id')->on('operator_routes')->onDelete('set null');

            // Indexes for performance
            $table->index(['operator_id', 'status']);
            $table->index(['current_route_id', 'status']);
            $table->index('bus_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operator_buses');
    }
}