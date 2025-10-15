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
        Schema::table('seat_layouts', function (Blueprint $table) {
            $table->integer('columns_per_row')->default(8)->after('seat_layout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seat_layouts', function (Blueprint $table) {
            $table->dropColumn('columns_per_row');
        });
    }
};