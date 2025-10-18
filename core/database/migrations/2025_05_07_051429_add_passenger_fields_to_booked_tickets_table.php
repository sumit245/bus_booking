<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPassengerFieldsToBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->string('passenger_phone')->nullable();
            $table->string('passenger_email')->nullable();
            $table->string('passenger_address')->nullable();
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
            $table->dropColumn(['passenger_phone', 'passenger_email', 'passenger_address']);
        });
    }
    
}
