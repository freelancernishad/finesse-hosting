<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('job_seeker_request_quote', function (Blueprint $table) {
            $table->decimal('salary', 10, 2)->nullable()->after('request_quote_id');
        });
    }

    public function down()
    {
        Schema::table('job_seeker_request_quote', function (Blueprint $table) {
            $table->dropColumn('salary');
        });
    }
};
