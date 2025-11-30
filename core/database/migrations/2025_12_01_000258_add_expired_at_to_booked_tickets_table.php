<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpiredAtToBookedTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            // Add expired_at timestamp to track when a pending ticket expired
            if (!Schema::hasColumn('booked_tickets', 'expired_at')) {
                $table->timestamp('expired_at')->nullable()->after('cancelled_at')->comment('Timestamp when pending ticket expired (status 0 → 4)');
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
            if (Schema::hasColumn('booked_tickets', 'expired_at')) {
                $table->dropColumn('expired_at');
            }
        });
    }
}
