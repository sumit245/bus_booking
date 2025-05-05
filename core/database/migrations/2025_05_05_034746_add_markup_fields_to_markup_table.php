<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMarkupFieldsToMarkupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('markup_table', function (Blueprint $table) {
            // Adding the missing columns
            $table->decimal('flat_markup', 10, 2)->default(0.00);  // Flat markup amount
            $table->decimal('percentage_markup', 5, 2)->default(0.00);  // Percentage markup amount
            $table->decimal('threshold', 10, 2)->default(0.00);  // Threshold for markup application
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('markup_table', function (Blueprint $table) {
            // Dropping the columns if we need to roll back
            $table->dropColumn(['flat_markup', 'percentage_markup', 'threshold']);
        });
    }
}
