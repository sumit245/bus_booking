<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Modify enum to include 'admin' value
        // MySQL/MariaDB doesn't support ALTER ENUM directly, so we use DB::statement
        DB::statement("ALTER TABLE `booked_tickets` MODIFY COLUMN `booking_source` ENUM('user', 'agent', 'operator', 'admin') NOT NULL DEFAULT 'user'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove 'admin' from enum
        DB::statement("ALTER TABLE `booked_tickets` MODIFY COLUMN `booking_source` ENUM('user', 'agent', 'operator') NOT NULL DEFAULT 'user'");
    }
};
