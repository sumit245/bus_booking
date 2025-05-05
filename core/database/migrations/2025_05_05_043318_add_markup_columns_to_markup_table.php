<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMarkupColumnsToMarkupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('markup_table', function (Blueprint $table) {
            $table->decimal('flat_markup', 10, 2)->default(0.00);  
            $table->decimal('percentage_markup', 5, 2)->default(0.00);  
            $table->decimal('threshold', 10, 2)->default(0.00);  
        });
    }
    
    public function down()
    {
        Schema::table('markup_table', function (Blueprint $table) {
            $table->dropColumn(['flat_markup', 'percentage_markup', 'threshold']);
        });
    }
    
}
