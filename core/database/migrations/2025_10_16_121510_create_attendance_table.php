<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('crew_assignment_id')->nullable(); // Link to crew assignment
            $table->date('attendance_date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'on_leave', 'sick_leave', 'emergency_leave']);
            $table->decimal('hours_worked', 5, 2)->default(0); // Hours worked in decimal format
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('check_in_location')->nullable(); // GPS coordinates or location name
            $table->string('check_out_location')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable(); // Staff ID who approved
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('crew_assignment_id')->references('id')->on('crew_assignments')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('staff')->onDelete('set null');

            // Indexes
            $table->index(['operator_id', 'attendance_date']);
            $table->index(['staff_id', 'attendance_date']);
            $table->index(['crew_assignment_id', 'attendance_date']);
            $table->index(['status', 'attendance_date']);

            // Ensure one attendance record per staff per date
            $table->unique(['staff_id', 'attendance_date'], 'unique_staff_attendance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance');
    }
}