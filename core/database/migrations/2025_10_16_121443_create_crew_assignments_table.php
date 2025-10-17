<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrewAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crew_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('operator_bus_id');
            $table->unsignedBigInteger('staff_id');
            $table->enum('role', ['driver', 'conductor', 'attendant']);
            $table->date('assignment_date');
            $table->date('start_date');
            $table->date('end_date')->nullable(); // NULL for ongoing assignments
            $table->time('shift_start_time')->nullable();
            $table->time('shift_end_time')->nullable();
            $table->enum('status', ['active', 'inactive', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->json('additional_details')->nullable(); // For storing route-specific details
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->foreign('operator_bus_id')->references('id')->on('operator_buses')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');

            // Indexes
            $table->index(['operator_id', 'operator_bus_id']);
            $table->index(['operator_bus_id', 'assignment_date']);
            $table->index(['staff_id', 'assignment_date']);
            $table->index(['role', 'status']);

            // Ensure one active assignment per role per bus per date
            $table->unique(['operator_bus_id', 'role', 'assignment_date', 'status'], 'unique_active_assignment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crew_assignments');
    }
}