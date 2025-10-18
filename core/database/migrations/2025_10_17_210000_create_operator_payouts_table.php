<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operator_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->decimal('total_revenue', 12, 2)->default(0.00);
            $table->decimal('platform_fee', 12, 2)->default(0.00);
            $table->decimal('payment_gateway_fee', 12, 2)->default(0.00);
            $table->decimal('tds_amount', 12, 2)->default(0.00);
            $table->decimal('other_deductions', 12, 2)->default(0.00);
            $table->decimal('net_payable', 12, 2)->default(0.00);
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->decimal('pending_amount', 12, 2)->default(0.00);
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'cancelled'])->default('pending');
            $table->date('payout_period_start');
            $table->date('payout_period_end');
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->text('payment_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamps();

            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->foreign('created_by_admin_id')->references('id')->on('admins')->onDelete('set null');
            $table->index(['operator_id', 'payout_period_start', 'payout_period_end'], 'op_payouts_period_idx');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operator_payouts');
    }
};
