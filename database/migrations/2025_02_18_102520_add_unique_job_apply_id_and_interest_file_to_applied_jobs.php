<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->string('unique_job_apply_id')->unique();  // For unique job apply ID
        });
    }

    public function down()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->dropColumn('unique_job_apply_id');
        });
    }

};
