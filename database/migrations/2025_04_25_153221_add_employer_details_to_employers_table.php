<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            // Business or Company Info
            $table->string('company_name')->nullable();
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('company_size')->nullable(); // e.g., 1-10, 11-50
            $table->string('business_location')->nullable();
            $table->string('years_in_operation')->nullable();
            $table->text('company_description')->nullable();
            $table->text('social_links')->nullable(); // JSON or comma-separated string

            // Employer Info
            $table->string('designation')->nullable();
            $table->text('bio')->nullable();

            // Contact Preferences
            $table->string('preferred_contact_time')->nullable();
            $table->string('preferred_contact_via')->nullable(); // email or phone
            $table->boolean('hired_before')->nullable(); // yes/no
        });
    }

    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'industry',
                'website',
                'company_size',
                'business_location',
                'years_in_operation',
                'company_description',
                'social_links',
                'designation',
                'bio',
                'preferred_contact_time',
                'preferred_contact_via',
                'hired_before',
            ]);
        });
    }
};
