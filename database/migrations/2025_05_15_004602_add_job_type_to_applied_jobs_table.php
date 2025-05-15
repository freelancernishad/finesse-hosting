<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobTypeToAppliedJobsTable extends Migration
{
    public function up()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->enum('job_type', ['waiting_list', 'hiring_request_apply', 'regular_job_apply'])->default('waiting_list')->after('on_call_status');
        });
    }

    public function down()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->dropColumn('job_type');
        });
    }
}
