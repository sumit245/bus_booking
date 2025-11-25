<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPointConversionToReferralSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            $table->boolean('use_point_system')->default(false)->after('is_enabled')->comment('Use points instead of currency');
            $table->integer('points_per_currency')->default(1)->after('use_point_system')->comment('How many points = 1 currency unit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            $table->dropColumn(['use_point_system', 'points_per_currency']);
        });
    }
}
