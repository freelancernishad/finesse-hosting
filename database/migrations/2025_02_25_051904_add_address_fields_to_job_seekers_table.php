<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->string('post_code')->nullable()->after('location');
            $table->string('city')->nullable()->after('post_code');
            $table->string('country')->nullable()->after('city');
        });
    }

    public function down()
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->dropColumn(['post_code', 'city', 'country']);
        });
    }
};

