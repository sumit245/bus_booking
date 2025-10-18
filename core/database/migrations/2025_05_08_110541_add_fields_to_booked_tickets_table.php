<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->string('bus_type')->nullable()->after('id');         // Adjust 'after' as needed
            $table->string('travel_name')->nullable()->after('bus_type');
            $table->time('departure_time')->nullable()->after('travel_name');
            $table->time('arrival_time')->nullable()->after('departure_time');
            $table->string('operator_pnr')->nullable()->after('arrival_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'bus_type',
                'travel_name',
                'departure_time',
                'arrival_time',
                'operator_pnr',
            ]);
        });
    }
}
