<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixMissingColumnsInBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("booked_tickets", function (Blueprint $table) {
            // Add departure_time column if it doesn't exist
            if (!Schema::hasColumn("booked_tickets", "departure_time")) {
                $table
                    ->time("departure_time")
                    ->nullable()
                    ->after("travel_name");
            }

            // Add arrival_time column if it doesn't exist
            if (!Schema::hasColumn("booked_tickets", "arrival_time")) {
                $table
                    ->time("arrival_time")
                    ->nullable()
                    ->after("departure_time");
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("booked_tickets", function (Blueprint $table) {
            // Drop the columns if they exist
            if (Schema::hasColumn("booked_tickets", "departure_time")) {
                $table->dropColumn("departure_time");
            }

            if (Schema::hasColumn("booked_tickets", "arrival_time")) {
                $table->dropColumn("arrival_time");
            }
        });
    }
}
