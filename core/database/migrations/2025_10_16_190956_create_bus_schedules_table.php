<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bus_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('operators')->onDelete('cascade');
            $table->foreignId('operator_bus_id')->constrained('operator_buses')->onDelete('cascade');
            $table->foreignId('operator_route_id')->constrained('operator_routes')->onDelete('cascade');

            // Schedule Details
            $table->string('schedule_name')->nullable(); // e.g., "Morning Service", "Evening Express"
            $table->time('departure_time');
            $table->time('arrival_time');
            $table->integer('estimated_duration_minutes')->nullable(); // Auto-calculated from departure/arrival

            // Days of Operation
            $table->json('days_of_operation')->nullable(); // ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
            $table->boolean('is_daily')->default(true);
            $table->date('start_date')->nullable(); // When this schedule becomes active
            $table->date('end_date')->nullable(); // When this schedule expires (null = indefinite)

            // Schedule Status
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('active'); // active, inactive, suspended, cancelled

            // Additional Details
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0); // For ordering schedules

            $table->timestamps();

            // Indexes for performance
            $table->index(['operator_id', 'is_active']);
            $table->index(['operator_bus_id', 'is_active']);
            $table->index(['operator_route_id', 'is_active']);
            $table->index(['departure_time', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_schedules');
    }
};