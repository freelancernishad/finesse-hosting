<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->string('otp')->nullable()->after('password');
            $table->boolean('email_verified')->default(false)->after('otp');
        });
    }

    public function down()
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->dropColumn(['otp', 'email_verified']);
        });
    }
};
