<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressAndContactToCountersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::table('counters', function (Blueprint $table) {
            if (!Schema::hasColumn('counters', 'address')) {
                $table->string('address')->nullable();
            }

            if (!Schema::hasColumn('counters', 'contact')) {
                $table->string('contact')->nullable();
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
        Schema::table('counters', function (Blueprint $table) {
            if (Schema::hasColumn('counters', 'address')) {
                $table->dropColumn('address');
            }

            if (Schema::hasColumn('counters', 'contact')) {
                $table->dropColumn('contact');
            }
        });
    }

}
