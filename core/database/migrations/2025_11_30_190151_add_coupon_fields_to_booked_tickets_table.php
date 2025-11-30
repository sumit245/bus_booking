<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCouponFieldsToBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('booked_tickets', 'coupon_code')) {
                $table->string('coupon_code', 255)->nullable()->after('total_amount')->comment('Coupon code applied to this booking');
            }
            if (!Schema::hasColumn('booked_tickets', 'coupon_discount')) {
                $table->decimal('coupon_discount', 10, 2)->default(0)->after('coupon_code')->comment('Discount amount from coupon');
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
            if (Schema::hasColumn('booked_tickets', 'coupon_code')) {
                $table->dropColumn('coupon_code');
            }
            if (Schema::hasColumn('booked_tickets', 'coupon_discount')) {
                $table->dropColumn('coupon_discount');
            }
        });
    }
}
