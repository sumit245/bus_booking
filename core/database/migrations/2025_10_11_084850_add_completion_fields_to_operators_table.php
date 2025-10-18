<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('operators', function (Blueprint $table) {
            // Add new document fields
            $table->string('aadhaar_card_front')->nullable()->after('aadhaar_card');
            $table->string('aadhaar_card_back')->nullable()->after('aadhaar_card_front');
            $table->string('business_license_image')->nullable()->after('driving_license');
            $table->string('business_license_pdf')->nullable()->after('business_license_image');

            // Add bank detail fields as separate columns
            $table->string('account_holder_name')->nullable()->after('cancelled_cheque');
            $table->string('account_number')->nullable()->after('account_holder_name');
            $table->string('ifsc_code')->nullable()->after('account_number');
            $table->string('gst_number')->nullable()->after('ifsc_code');
            $table->string('bank_name')->nullable()->after('gst_number');

            // Add completion tracking fields
            $table->boolean('basic_details_completed')->default(0)->after('bank_name');
            $table->boolean('company_details_completed')->default(0)->after('basic_details_completed');
            $table->boolean('documents_completed')->default(0)->after('company_details_completed');
            $table->boolean('bank_details_completed')->default(0)->after('documents_completed');
            $table->boolean('all_details_completed')->default(0)->after('bank_details_completed');
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
            $table->dropColumn([
                'aadhaar_card_front',
                'aadhaar_card_back',
                'business_license_image',
                'business_license_pdf',
                'account_holder_name',
                'account_number',
                'ifsc_code',
                'gst_number',
                'bank_name',
                'basic_details_completed',
                'company_details_completed',
                'documents_completed',
                'bank_details_completed',
                'all_details_completed'
            ]);
        });
    }
};