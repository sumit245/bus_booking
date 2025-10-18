<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageFieldsToCouponTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupon_table', function (Blueprint $table) {
            //

            $table->string('banner_image')->nullable()->after('expiry_date');
            $table->string('sticker_image')->nullable()->after('banner_image');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupon_table', function (Blueprint $table) {
            //
        });
    }
}
