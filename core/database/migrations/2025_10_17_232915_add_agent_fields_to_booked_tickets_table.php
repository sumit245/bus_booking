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
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('agent_id')->nullable()->after('operator_booking_id');
            $table->decimal('agent_commission_amount', 12, 2)->default(0)->after('agent_commission');
            $table->enum('booking_source', ['user', 'agent', 'operator'])->default('user')->after('booking_type');
            $table->decimal('total_commission_charged', 12, 2)->default(0)->after('agent_commission_amount'); // Total commission charged to passenger

            // Foreign key constraint
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('set null');

            // Indexes
            $table->index('agent_id');
            $table->index('booking_source');
            $table->index(['booking_source', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booked_tickets', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['booking_source']);
            $table->dropIndex(['booking_source', 'created_at']);
            $table->dropColumn([
                'agent_id',
                'agent_commission_amount',
                'booking_source',
                'total_commission_charged'
            ]);
        });
    }
};