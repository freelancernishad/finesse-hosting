<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCertificateToAppliedJobsTable extends Migration
{
    public function up()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
             $table->json('certificate')->nullable()->after('job_type');
        });
    }

    public function down()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->dropColumn('certificate');
        });
    }
}
