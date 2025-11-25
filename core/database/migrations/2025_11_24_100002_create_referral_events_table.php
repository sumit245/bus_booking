<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_user_id')->nullable()->comment('User who shared the referral');
            $table->unsignedBigInteger('referee_user_id')->nullable()->comment('User who used the referral');
            $table->unsignedBigInteger('referral_code_id');
            $table->enum('type', ['install', 'signup', 'booking'])->comment('Event type');
            $table->unsignedBigInteger('ticket_id')->nullable()->comment('For booking events');
            $table->json('context_json')->nullable()->comment('Additional event data');
            $table->timestamp('triggered_at');
            $table->timestamps();

            $table->foreign('referrer_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('referee_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('referral_code_id')->references('id')->on('referral_codes')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('booked_tickets')->onDelete('set null');

            $table->index(['referral_code_id', 'type']);
            $table->index('triggered_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referral_events');
    }
}
