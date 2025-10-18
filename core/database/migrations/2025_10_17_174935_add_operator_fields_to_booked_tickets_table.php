<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOperatorFieldsToBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            // Add operator-related fields
            $table->unsignedBigInteger('operator_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('operator_booking_id')->nullable()->after('operator_id');
            $table->string('booking_id')->nullable()->after('operator_booking_id');
            $table->string('ticket_no')->nullable()->after('booking_id');

            // departure_time and arrival_time already exist - skip them

            // Add additional fields
            $table->string('payment_status')->nullable()->after('status');
            $table->string('booking_type')->nullable()->after('payment_status');
            $table->string('boarding_point')->nullable()->after('pickup_point');
            $table->string('seat_numbers')->nullable()->after('seats');
            $table->decimal('total_amount', 20, 8)->default(0)->after('sub_total');
            $table->decimal('paid_amount', 20, 8)->default(0)->after('total_amount');
            $table->text('booking_reason')->nullable()->after('booking_type');
            $table->text('notes')->nullable()->after('booking_reason');
            $table->json('passenger_phones')->nullable()->after('passenger_phone');
            $table->json('passenger_emails')->nullable()->after('passenger_email');
            $table->unsignedBigInteger('bus_id')->nullable()->after('trip_id');
            $table->unsignedBigInteger('route_id')->nullable()->after('bus_id');
            $table->unsignedBigInteger('schedule_id')->nullable()->after('route_id');
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
            $table->dropColumn([
                'operator_id',
                'operator_booking_id',
                'booking_id',
                'ticket_no',
                'departure_time',
                'arrival_time',
                'payment_status',
                'booking_type',
                'boarding_point',
                'seat_numbers',
                'total_amount',
                'paid_amount',
                'booking_reason',
                'notes',
                'passenger_phones',
                'passenger_emails',
                'bus_id',
                'route_id',
                'schedule_id'
            ]);
        });
    }
}
