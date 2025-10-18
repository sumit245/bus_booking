<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingBusPriceFieldsToOperatorBusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operator_buses', function (Blueprint $table) {
            // Add missing BusPrice fields from the API structure
            $table->decimal('tax', 8, 2)->default(0)->after('agent_commission');
            $table->decimal('other_charges', 8, 2)->default(0)->after('tax');
            $table->decimal('discount', 8, 2)->default(0)->after('other_charges');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operator_buses', function (Blueprint $table) {
            $table->dropColumn(['tax', 'other_charges', 'discount']);
        });
    }
}