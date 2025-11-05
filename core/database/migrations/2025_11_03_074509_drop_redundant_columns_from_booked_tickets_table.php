<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropRedundantColumnsFromBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            // Drop redundant columns
            $table->dropColumn('pickup_point');      // Redundant - same as boarding_point
            $table->dropColumn('boarding_point');   // Redundant - use boarding_point_details instead
            $table->dropColumn('seat_numbers');      // Redundant - seats already stores array of seat names
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            // Re-add columns with their original structure
            $table->unsignedInteger('pickup_point')->default(0)->after('source_destination');
            $table->string('boarding_point')->nullable()->after('pickup_point');
            $table->string('seat_numbers')->nullable()->after('seats');
        });
    }
}
