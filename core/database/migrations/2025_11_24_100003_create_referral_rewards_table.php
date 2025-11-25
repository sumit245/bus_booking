<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referral_event_id');
            $table->unsignedBigInteger('beneficiary_user_id')->comment('User who receives the reward');
            $table->enum('reward_type', ['fixed', 'percent', 'percent_of_ticket']);
            $table->decimal('basis_amount', 10, 2)->default(0)->comment('Original amount used for calculation');
            $table->decimal('amount_awarded', 10, 2)->comment('Final reward amount');
            $table->enum('status', ['pending', 'confirmed', 'reversed'])->default('pending');
            $table->string('reason')->nullable()->comment('Reason for reversal if any');
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();

            $table->foreign('referral_event_id')->references('id')->on('referral_events')->onDelete('cascade');
            $table->foreign('beneficiary_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['beneficiary_user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referral_rewards');
    }
}
