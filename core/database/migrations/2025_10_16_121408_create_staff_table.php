<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->string('employee_id')->unique(); // Unique employee ID
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('whatsapp_number')->nullable(); // For WhatsApp notifications
            $table->enum('role', ['driver', 'conductor', 'attendant', 'manager', 'other']);
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth');
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('pincode');
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone');
            $table->string('emergency_contact_relation');

            // Employment details
            $table->date('joining_date');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'temporary']);
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('total_salary', 10, 2)->default(0);
            $table->enum('salary_frequency', ['monthly', 'weekly', 'daily'])->default('monthly');

            // Documents
            $table->string('aadhar_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('driving_license_number')->nullable(); // For drivers
            $table->date('driving_license_expiry')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();

            // Profile and documents
            $table->string('profile_photo')->nullable();
            $table->string('aadhar_document')->nullable();
            $table->string('pan_document')->nullable();
            $table->string('driving_license_document')->nullable();
            $table->string('passport_document')->nullable();
            $table->string('other_documents')->nullable(); // JSON array of other documents

            // Status and preferences
            $table->boolean('is_active')->default(true);
            $table->boolean('whatsapp_notifications_enabled')->default(false);
            $table->text('notes')->nullable();
            $table->json('preferences')->nullable(); // For storing additional preferences

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');

            // Indexes
            $table->index(['operator_id', 'role']);
            $table->index(['operator_id', 'is_active']);
            $table->index('employee_id');
            $table->index('phone');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff');
    }
}