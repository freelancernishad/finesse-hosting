<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToRequestQuotesTable extends Migration
{
    public function up()
    {
        Schema::table('hiring_requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'assigned', 'completed', 'canceled'])
                  ->default('pending')
                  ->after('event_details');
        });
    }

    public function down()
    {
        Schema::table('hiring_requests', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
