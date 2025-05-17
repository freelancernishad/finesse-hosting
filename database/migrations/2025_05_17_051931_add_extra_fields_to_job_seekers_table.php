<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraFieldsToJobSeekersTable extends Migration
{
    public function up()
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->json('language')->nullable()->after('resume');
            $table->json('skills')->nullable()->after('language');
            $table->json('certificate')->nullable()->after('skills');
            $table->json('education')->nullable()->after('certificate');
            $table->json('employment_history')->nullable()->after('education');
        });
    }

    public function down()
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->dropColumn(['language', 'skills', 'certificate', 'education', 'employment_history']);
        });
    }
}
