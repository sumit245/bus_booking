<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColoumnsToBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('booked_tickets', 'passenger_name')) {
                $table->string('passenger_name')->nullable();
            }
            if (!Schema::hasColumn('booked_tickets', 'passenger_phone')) {
                $table->string('passenger_phone')->nullable();
            }
            if (!Schema::hasColumn('booked_tickets', 'passenger_email')) {
                $table->string('passenger_email')->nullable();
            }
            if (!Schema::hasColumn('booked_tickets', 'passenger_address')) {
                $table->string('passenger_address')->nullable();
            }
            if (!Schema::hasColumn('booked_tickets', 'passenger_age')) {
                $table->integer('passenger_age')->nullable();
            }
            if (!Schema::hasColumn('booked_tickets', 'passenger_names')) {
                $table->text('passenger_names')->nullable();
            }
            if (!Schema::hasColumn('booked_tickets', 'api_response')) {
                $table->text('api_response')->nullable();
            }
            
            // Fix date_of_journey column if it has invalid default
            DB::statement('ALTER TABLE booked_tickets MODIFY date_of_journey DATE NULL');
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
