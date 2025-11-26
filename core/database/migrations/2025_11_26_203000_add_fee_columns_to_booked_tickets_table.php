<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeeColumnsToBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            // Add fee breakdown columns
            if (!Schema::hasColumn('booked_tickets', 'service_charge')) {
                $table->decimal('service_charge', 10, 2)->default(0)->after('sub_total')->comment('Service charge amount');
            }
            if (!Schema::hasColumn('booked_tickets', 'service_charge_percentage')) {
                $table->decimal('service_charge_percentage', 5, 2)->default(0)->after('service_charge')->comment('Service charge percentage applied');
            }
            if (!Schema::hasColumn('booked_tickets', 'platform_fee')) {
                $table->decimal('platform_fee', 10, 2)->default(0)->after('service_charge_percentage')->comment('Platform fee amount');
            }
            if (!Schema::hasColumn('booked_tickets', 'platform_fee_percentage')) {
                $table->decimal('platform_fee_percentage', 5, 2)->default(0)->after('platform_fee')->comment('Platform fee percentage applied');
            }
            if (!Schema::hasColumn('booked_tickets', 'platform_fee_fixed')) {
                $table->decimal('platform_fee_fixed', 10, 2)->default(0)->after('platform_fee_percentage')->comment('Platform fee fixed amount');
            }
            if (!Schema::hasColumn('booked_tickets', 'gst')) {
                $table->decimal('gst', 10, 2)->default(0)->after('platform_fee_fixed')->comment('GST amount');
            }
            if (!Schema::hasColumn('booked_tickets', 'gst_percentage')) {
                $table->decimal('gst_percentage', 5, 2)->default(0)->after('gst')->comment('GST percentage applied');
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
        Schema::table('booked_tickets', function (Blueprint $table) {
            $columns = [
                'service_charge',
                'service_charge_percentage',
                'platform_fee',
                'platform_fee_percentage',
                'platform_fee_fixed',
                'gst',
                'gst_percentage'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('booked_tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
