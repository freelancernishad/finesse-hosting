<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddModelNameToHiringRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('hiring_requests', function (Blueprint $table) {
            $table->string('model_name')->nullable()->after('end_date');
        });
    }

    public function down()
    {
        Schema::table('hiring_requests', function (Blueprint $table) {
            $table->dropColumn('model_name');
        });
    }
}
