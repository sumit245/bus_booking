<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operator_buses', function (Blueprint $table) {
            $table->json('cancellation_policy')->nullable()->after('gst_rate');
            $table->boolean('use_default_cancellation_policy')->default(true)->after('cancellation_policy');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operator_buses', function (Blueprint $table) {
            $table->dropColumn(['cancellation_policy', 'use_default_cancellation_policy']);
        });
    }
};
