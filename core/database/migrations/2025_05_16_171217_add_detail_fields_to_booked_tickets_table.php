<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailFieldsToBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::table('booked_tickets', function (Blueprint $table) {
        if (!Schema::hasColumn('booked_tickets', 'boarding_point_details')) {
            $table->json('boarding_point_details')->nullable()->after('pickup_point');
        }
        if (!Schema::hasColumn('booked_tickets', 'dropping_point_details')) {
            $table->json('dropping_point_details')->nullable()->after('dropping_point');
        }
        if (!Schema::hasColumn('booked_tickets', 'bus_details')) {
            $table->json('bus_details')->nullable()->after('api_response');
        }
        if (!Schema::hasColumn('booked_tickets', 'cancellation_remarks')) {
            $table->text('cancellation_remarks')->nullable()->after('status');
        }
        if (!Schema::hasColumn('booked_tickets', 'cancelled_at')) {
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_remarks');
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
