<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusRouteHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bus_route_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bus_id'); // Foreign key to operator_buses
            $table->unsignedBigInteger('route_id'); // Foreign key to operator_routes
            $table->date('assigned_date'); // When bus was assigned to this route
            $table->date('unassigned_date')->nullable(); // When bus was removed from this route
            $table->text('notes')->nullable(); // Additional notes about the assignment
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('bus_id')->references('id')->on('operator_buses')->onDelete('cascade');
            $table->foreign('route_id')->references('id')->on('operator_routes')->onDelete('cascade');

            // Indexes
            $table->index(['bus_id', 'assigned_date']);
            $table->index(['route_id', 'assigned_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_route_history');
    }
}