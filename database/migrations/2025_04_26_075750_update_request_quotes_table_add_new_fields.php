<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRequestQuotesTableAddNewFields extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('request_quotes', function (Blueprint $table) {
            $table->string('selected_industry')->nullable()->after('id');
            $table->json('selected_categories')->nullable()->after('selected_industry');
            $table->json('job_descriptions')->nullable()->after('selected_categories');
            $table->boolean('is_use_my_current_company_location')->default(false)->after('job_descriptions');
            $table->json('job_location')->nullable()->after('is_use_my_current_company_location');
            $table->string('years_of_experience')->nullable()->after('job_location');
            $table->text('reason_for_hire')->nullable()->after('years_of_experience');
            $table->text('note')->nullable()->after('reason_for_hire');
            $table->boolean('hire_for_my_current_company')->default(false)->after('note');
            $table->json('company_info')->nullable()->after('hire_for_my_current_company');
            $table->integer('total_hours')->nullable()->after('company_info');
            $table->timestamp('start_date')->nullable()->after('total_hours');
            $table->timestamp('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_quotes', function (Blueprint $table) {
            $table->dropColumn([
                'selected_industry',
                'selected_categories',
                'job_descriptions',
                'is_use_my_current_company_location',
                'job_location',
                'years_of_experience',
                'reason_for_hire',
                'note',
                'hire_for_my_current_company',
                'company_info',
                'total_hours',
                'start_date',
                'end_date',
            ]);
        });
    }
}
