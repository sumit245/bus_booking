<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPassengerNamesToBookedTicketsTable extends Migration
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
   $table->json('passenger_names')->nullable();
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
