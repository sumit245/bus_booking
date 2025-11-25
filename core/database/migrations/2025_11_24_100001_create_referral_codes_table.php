<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Linked after signup');
            $table->char('code', 6)->unique()->comment('6-character alphanumeric code');
            $table->enum('source', ['pwa', 'app', 'web'])->default('app');
            $table->string('device_id')->nullable();
            $table->integer('total_clicks')->default(0);
            $table->integer('total_installs')->default(0);
            $table->integer('total_signups')->default(0);
            $table->integer('total_bookings')->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('code');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referral_codes');
    }
}
