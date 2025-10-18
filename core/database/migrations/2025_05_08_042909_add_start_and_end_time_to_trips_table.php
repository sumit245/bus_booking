<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStartAndEndTimeToTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('trips', function (Blueprint $table) {
        $table->time('start_time')->nullable();
        $table->time('end_time')->nullable();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
{
    Schema::table('trips', function (Blueprint $table) {
        $table->dropColumn(['start_time', 'end_time']);
    });
}
}
