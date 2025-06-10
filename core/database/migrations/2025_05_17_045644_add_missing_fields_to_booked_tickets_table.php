<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingFieldsToBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
  public function up()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('booked_tickets', 'bus_type')) {
                $table->string('bus_type')->nullable()->after('trip_id');
            }
            
            if (!Schema::hasColumn('booked_tickets', 'travel_name')) {
                $table->string('travel_name')->nullable()->after('bus_type');
            }
            
            if (!Schema::hasColumn('booked_tickets', 'departure_time')) {
                $table->string('departure_time')->nullable()->after('travel_name');
            }
            
            if (!Schema::hasColumn('booked_tickets', 'arrival_time')) {
                $table->string('arrival_time')->nullable()->after('departure_time');
            }
            
            if (!Schema::hasColumn('booked_tickets', 'operator_pnr')) {
                $table->string('operator_pnr')->nullable()->after('arrival_time');
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
        Schema::table('booked_tickets', function (Blueprint $table) {
            //
        });
    }
}
