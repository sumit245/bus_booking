<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(true);

            // Reward configuration
            $table->enum('reward_type', ['fixed', 'percent', 'percent_of_ticket'])->default('percent_of_ticket');
            $table->decimal('fixed_amount', 10, 2)->default(0)->comment('Fixed amount per referral');
            $table->decimal('percent_share', 5, 2)->default(0)->comment('Percentage of a base amount');
            $table->decimal('percent_of_ticket', 5, 2)->default(10)->comment('Percentage of first booking amount');

            // Event triggers
            $table->boolean('reward_on_install')->default(false);
            $table->boolean('reward_on_signup')->default(false);
            $table->boolean('reward_on_first_booking')->default(true);

            // Beneficiaries
            $table->boolean('reward_referrer')->default(true)->comment('Give reward to person who shared');
            $table->boolean('reward_referee')->default(false)->comment('Give reward to person who signed up');

            // Limits and constraints
            $table->decimal('min_booking_amount', 10, 2)->default(0)->comment('Minimum booking amount for eligibility');
            $table->integer('reward_credit_days')->default(0)->comment('Days to wait before crediting reward');
            $table->integer('daily_cap_per_referrer')->nullable()->comment('Max referrals per day per user');
            $table->integer('max_referrals_per_user')->nullable()->comment('Lifetime max referrals per user');

            // Message customization
            $table->text('share_message')->nullable()->comment('Custom message for sharing');
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // Insert default settings
        DB::table('referral_settings')->insert([
            'is_enabled' => true,
            'reward_type' => 'percent_of_ticket',
            'percent_of_ticket' => 10,
            'reward_on_first_booking' => true,
            'reward_referrer' => true,
            'reward_referee' => false,
            'min_booking_amount' => 100,
            'reward_credit_days' => 0,
            'share_message' => 'Join Ghumantoo and get amazing bus booking deals! Use my referral code to get started.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referral_settings');
    }
}
