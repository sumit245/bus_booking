<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferralFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('referred_by')->nullable()->after('id')->comment('User ID who referred this user');
            $table->unsignedBigInteger('referral_code_id')->nullable()->after('referred_by')->comment('Referral code used during signup');
            $table->boolean('has_completed_first_booking')->default(false)->after('referral_code_id');

            $table->foreign('referred_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('referral_code_id')->references('id')->on('referral_codes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropForeign(['referral_code_id']);
            $table->dropColumn(['referred_by', 'referral_code_id', 'has_completed_first_booking']);
        });
    }
}
