<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAreaAndTypeOfHiringToRequestQuotes extends Migration
{
    public function up()
    {
        Schema::table('request_quotes', function (Blueprint $table) {
            $table->string('area')->nullable(); // Add area column
            $table->string('type_of_hiring')->nullable(); // Add type_of_hiring column
        });
    }

    public function down()
    {
        Schema::table('request_quotes', function (Blueprint $table) {
            $table->dropColumn(['area', 'type_of_hiring']);
        });
    }
}
