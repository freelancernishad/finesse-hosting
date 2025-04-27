<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpectedJoiningDateAndSalariesToHiringRequestsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hiring_requests', function (Blueprint $table) {
            $table->dateTime('expected_joining_date')->nullable()->after('company_info');
            $table->decimal('min_yearly_salary', 15, 2)->nullable()->after('expected_joining_date');
            $table->decimal('mix_yearly_salary', 15, 2)->nullable()->after('min_yearly_salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hiring_requests', function (Blueprint $table) {
            $table->dropColumn('expected_joining_date');
            $table->dropColumn('min_yearly_salary');
            $table->dropColumn('mix_yearly_salary');
        });
    }
}
