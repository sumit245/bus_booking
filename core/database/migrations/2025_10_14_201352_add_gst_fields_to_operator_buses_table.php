<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGstFieldsToOperatorBusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operator_buses', function (Blueprint $table) {
            // Add GST fields from the API structure
            $table->decimal('cgst_amount', 8, 2)->default(0)->after('tds');
            $table->decimal('cgst_rate', 5, 2)->default(0)->after('cgst_amount');
            $table->decimal('igst_amount', 8, 2)->default(0)->after('cgst_rate');
            $table->decimal('igst_rate', 5, 2)->default(0)->after('igst_amount');
            $table->decimal('sgst_amount', 8, 2)->default(0)->after('igst_rate');
            $table->decimal('sgst_rate', 5, 2)->default(0)->after('sgst_amount');
            $table->decimal('taxable_amount', 8, 2)->default(0)->after('sgst_rate');
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
            $table->dropColumn([
                'cgst_amount',
                'cgst_rate',
                'igst_amount',
                'igst_rate',
                'sgst_amount',
                'sgst_rate',
                'taxable_amount'
            ]);
        });
    }
}