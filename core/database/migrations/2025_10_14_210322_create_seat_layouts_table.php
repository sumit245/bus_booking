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
        Schema::create('seat_layouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_bus_id');
            $table->string('layout_name')->nullable();
            $table->integer('total_seats')->default(0);
            $table->integer('upper_deck_seats')->default(0);
            $table->integer('lower_deck_seats')->default(0);
            $table->json('layout_data'); // Stores the complete layout structure
            $table->text('html_layout'); // Stores the generated HTML layout
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('operator_bus_id')->references('id')->on('operator_buses')->onDelete('cascade');
            $table->index(['operator_bus_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_layouts');
    }
};