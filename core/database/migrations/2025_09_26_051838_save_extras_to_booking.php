<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SaveExtrasToBooking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            //
            $table->string('api_invoice_amount')->nullable()->after('api_invoice');
            $table->string('api_invoice_date')->nullable()->after('api_invoice_amount');
            $table->string('api_booking_id')->nullable()->after('api_invoice_date');
            $table->string('api_ticket_no')->nullable()->after('api_booking_id');
            $table->string('agent_commission')->nullable()->after('api_ticket_no');
            $table->string('tds_from_api')->nullable()->after('agent_commission');
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
