<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOperatorsTableBusinessLicenseField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operators', function (Blueprint $table) {
            // Drop the old business license fields
            $table->dropColumn(['business_license_image', 'business_license_pdf']);

            // Add the new business license field
            $table->string('business_license')->nullable()->after('aadhaar_card_back');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('operators', function (Blueprint $table) {
            // Drop the new business license field
            $table->dropColumn('business_license');

            // Add back the old business license fields
            $table->string('business_license_image')->nullable()->after('aadhaar_card_back');
            $table->string('business_license_pdf')->nullable()->after('business_license_image');
        });
    }
}