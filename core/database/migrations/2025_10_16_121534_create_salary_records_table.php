<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalaryRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salary_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('staff_id');
            $table->string('salary_period'); // e.g., "2025-10" for October 2025
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->enum('status', ['pending', 'calculated', 'approved', 'paid', 'cancelled'])->default('pending');

            // Salary components
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('overtime_amount', 10, 2)->default(0);
            $table->decimal('bonus', 10, 2)->default(0);
            $table->decimal('incentives', 10, 2)->default(0);
            $table->decimal('gross_salary', 10, 2)->default(0);

            // Deductions
            $table->decimal('late_deduction', 10, 2)->default(0);
            $table->decimal('absent_deduction', 10, 2)->default(0);
            $table->decimal('advance_deduction', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);

            // Final amounts
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance_amount', 10, 2)->default(0);

            // Payment details
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'upi'])->nullable();
            $table->string('payment_reference')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('payment_notes')->nullable();

            // Approval and processing
            $table->unsignedBigInteger('calculated_by')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->text('notes')->nullable();
            $table->json('additional_data')->nullable(); // For storing extra salary components
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('calculated_by')->references('id')->on('staff')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('staff')->onDelete('set null');
            $table->foreign('paid_by')->references('id')->on('staff')->onDelete('set null');

            // Indexes
            $table->index(['operator_id', 'salary_period']);
            $table->index(['staff_id', 'salary_period']);
            $table->index(['status', 'salary_period']);
            $table->index('payment_date');

            // Ensure one salary record per staff per period
            $table->unique(['staff_id', 'salary_period'], 'unique_staff_salary_period');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salary_records');
    }
}