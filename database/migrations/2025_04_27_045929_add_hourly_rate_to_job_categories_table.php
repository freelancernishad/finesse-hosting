<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_categories', function (Blueprint $table) {
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('status');
            // 8 total digits, 2 decimal places (adjust if needed)
        });
    }

    public function down(): void
    {
        Schema::table('job_categories', function (Blueprint $table) {
            $table->dropColumn('hourly_rate');
        });
    }
};
