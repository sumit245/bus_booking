<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDroppingPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dropping_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_route_id');
            $table->string('point_name'); // e.g., "Bus Stand Satna"
            $table->text('point_address')->nullable(); // Address if available
            $table->string('point_location'); // e.g., "Bus Stand"
            $table->string('point_landmark')->nullable(); // Landmark if available
            $table->string('contact_number')->nullable(); // Contact number if available
            $table->integer('point_index'); // Order of dropping point (1, 2, 3, etc.)
            $table->time('point_time'); // Time when bus arrives at this point
            $table->boolean('status')->default(1); // Active/Inactive
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('operator_route_id')->references('id')->on('operator_routes')->onDelete('cascade');

            // Indexes
            $table->index(['operator_route_id', 'point_index']);
            $table->index(['operator_route_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dropping_points');
    }
}