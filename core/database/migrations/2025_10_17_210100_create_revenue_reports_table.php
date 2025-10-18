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
        Schema::create('revenue_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->date('report_date');
            $table->decimal('total_tickets', 10, 0)->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0.00);
            $table->decimal('user_bookings_revenue', 12, 2)->default(0.00);
            $table->decimal('operator_bookings_revenue', 12, 2)->default(0.00);
            $table->decimal('unit_price_total', 12, 2)->default(0.00);
            $table->decimal('sub_total_total', 12, 2)->default(0.00);
            $table->decimal('agent_commission_total', 12, 2)->default(0.00);
            $table->decimal('platform_commission', 12, 2)->default(0.00);
            $table->decimal('payment_gateway_fees', 12, 2)->default(0.00);
            $table->decimal('tds_amount', 12, 2)->default(0.00);
            $table->decimal('net_payable', 12, 2)->default(0.00);
            $table->json('detailed_breakdown')->nullable();
            $table->enum('report_type', ['daily', 'weekly', 'monthly', 'custom'])->default('daily');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamps();

            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade');
            $table->index(['operator_id', 'report_date']);
            $table->index(['operator_id', 'report_type']);
            $table->unique(['operator_id', 'report_date', 'report_type'], 'rev_reports_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_reports');
    }
};
