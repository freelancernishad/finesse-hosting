<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToHiringRequestJobSeekerTable extends Migration
{
    public function up()
    {
        Schema::table('hiring_request_job_seeker', function (Blueprint $table) {
            $table->enum('status', ['assigned', 'released'])->default('assigned')->after('total_amount');
        });
    }

    public function down()
    {
        Schema::table('hiring_request_job_seeker', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
