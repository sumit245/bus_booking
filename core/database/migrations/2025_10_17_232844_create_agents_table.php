<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by_admin_id')->nullable();
            $table->json('documents')->nullable(); // KYC documents
            $table->string('profile_image')->nullable();
            $table->text('address')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('aadhaar_number')->nullable();
            $table->integer('total_bookings')->default(0);
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->decimal('pending_earnings', 12, 2)->default(0);
            $table->unsignedBigInteger('created_by_admin_id')->nullable(); // For admin-created agents
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('verified_by_admin_id')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('created_by_admin_id')->references('id')->on('admins')->onDelete('set null');

            // Indexes
            $table->index('status');
            $table->index('email');
            $table->index('phone');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agents');
    }
};