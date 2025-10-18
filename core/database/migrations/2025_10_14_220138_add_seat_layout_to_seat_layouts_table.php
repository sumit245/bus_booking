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
            $table->string('seat_layout', 10)->default('2x2')->after('deck_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seat_layouts', function (Blueprint $table) {
            $table->dropColumn('seat_layout');
        });
    }
};