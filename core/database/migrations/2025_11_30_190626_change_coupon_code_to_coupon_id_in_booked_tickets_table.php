<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCouponCodeToCouponIdInBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            // Drop coupon_code column if it exists
            if (Schema::hasColumn('booked_tickets', 'coupon_code')) {
                $table->dropColumn('coupon_code');
            }

            // Add coupon_id column (foreign key to coupon_table)
            if (!Schema::hasColumn('booked_tickets', 'coupon_id')) {
                $table->unsignedBigInteger('coupon_id')->nullable()->after('total_amount')->comment('Coupon ID from coupon_table - for analytics (operator/admin/referral coupons)');

                // Add foreign key constraint
                $table->foreign('coupon_id')->references('id')->on('coupon_table')->onDelete('set null');

                // Add index for better query performance
                $table->index('coupon_id');
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
            // Drop foreign key and index first
            if (Schema::hasColumn('booked_tickets', 'coupon_id')) {
                $table->dropForeign(['coupon_id']);
                $table->dropIndex(['coupon_id']);
                $table->dropColumn('coupon_id');
            }

            // Restore coupon_code column
            if (!Schema::hasColumn('booked_tickets', 'coupon_code')) {
                $table->string('coupon_code', 255)->nullable()->after('total_amount')->comment('Coupon code applied to this booking');
            }
        });
    }
}
