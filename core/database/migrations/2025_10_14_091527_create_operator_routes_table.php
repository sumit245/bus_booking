<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperatorRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('operator_routes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->string('route_name');
            $table->integer('origin_city_id'); // References cities.id
            $table->integer('destination_city_id'); // References cities.id
            $table->text('description')->nullable();
            $table->decimal('distance', 8, 2)->nullable(); // Distance in km
            $table->decimal('estimated_duration', 4, 1)->nullable(); // Estimated travel time in hours
            $table->decimal('base_fare', 8, 2)->nullable(); // Base fare for the route
            $table->boolean('status')->default(1); // Active/Inactive
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->foreign('origin_city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->foreign('destination_city_id')->references('id')->on('cities')->onDelete('cascade');

            // Indexes
            $table->index(['operator_id', 'status']);
            $table->index(['origin_city_id', 'destination_city_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operator_routes');
    }
}