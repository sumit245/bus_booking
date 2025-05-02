<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToMarkupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('markup_table', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
        });
        
    }
    
    public function down()
    {
        Schema::table('markup_table', function (Blueprint $table) {
            $table->dropColumn(['id', 'amount']);
        });
    }
    
}
