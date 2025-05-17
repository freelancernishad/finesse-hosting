<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnCallStatusToJobSeekersTable extends Migration
{
    public function up()
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->enum('on_call_status', ['Stand by', 'On-call'])->default('Stand by')->after('employment_history');
        });
    }

    public function down()
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->dropColumn('on_call_status');
        });
    }
}
