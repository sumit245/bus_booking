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
        Schema::table('crew_assignments', function (Blueprint $table) {
            // Add bus_schedule_id column
            $table->foreignId('bus_schedule_id')->nullable()->after('operator_id')->constrained('bus_schedules')->onDelete('cascade');

            // Drop the unique constraint that includes operator_bus_id
            $table->dropUnique('unique_active_assignment');

            // Add new unique constraint with bus_schedule_id instead
            $table->unique(['bus_schedule_id', 'role', 'assignment_date', 'status'], 'unique_schedule_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crew_assignments', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('unique_schedule_assignment');

            // Restore the old unique constraint
            $table->unique(['operator_bus_id', 'role', 'assignment_date', 'status'], 'unique_active_assignment');

            // Drop the bus_schedule_id column
            $table->dropForeign(['bus_schedule_id']);
            $table->dropColumn('bus_schedule_id');
        });
    }
};
