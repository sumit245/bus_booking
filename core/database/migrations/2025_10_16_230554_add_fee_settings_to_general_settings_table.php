<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeeSettingsToGeneralSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->decimal('gst_percentage', 5, 2)->default(0)->after('cur_text')->comment('GST percentage (e.g., 18 for 18%)');
            $table->decimal('service_charge_percentage', 5, 2)->default(0)->after('gst_percentage')->comment('Service charge percentage');
            $table->decimal('platform_fee_percentage', 5, 2)->default(0)->after('service_charge_percentage')->comment('Platform fee percentage');
            $table->decimal('platform_fee_fixed', 10, 2)->default(0)->after('platform_fee_percentage')->comment('Fixed platform fee amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['gst_percentage', 'service_charge_percentage', 'platform_fee_percentage', 'platform_fee_fixed']);
        });
    }
}
