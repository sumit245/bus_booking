<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralClicksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referral_code_id');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer_url')->nullable();
            $table->timestamp('clicked_at');
            $table->timestamps();

            $table->foreign('referral_code_id')->references('id')->on('referral_codes')->onDelete('cascade');
            $table->index('referral_code_id');
            $table->index('clicked_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referral_clicks');
    }
}
