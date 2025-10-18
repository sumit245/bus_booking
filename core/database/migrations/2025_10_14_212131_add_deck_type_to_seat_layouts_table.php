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
            $table->enum('deck_type', ['single', 'double'])->default('double')->after('layout_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seat_layouts', function (Blueprint $table) {
            $table->dropColumn('deck_type');
        });
    }
};