<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToAppliedJobsTable extends Migration
{
    public function up()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->text('describe_yourself')->nullable();
            $table->string('resume')->nullable();
            $table->text('cover_letter')->nullable();
            $table->text('experience')->nullable();
            $table->enum('preferred_contact_method', ['email', 'phone'])->nullable();
            $table->enum('on_call_status', ['Stand by', 'On-call'])->nullable();
        });
    }

    public function down()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'describe_yourself',
                'resume',
                'cover_letter',
                'experience',
                'preferred_contact_method',
                'on_call_status',
            ]);
        });
    }
}
