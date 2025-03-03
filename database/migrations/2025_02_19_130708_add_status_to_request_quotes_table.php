<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToRequestQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_quotes', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'assigned', 'completed', 'canceled'])
                  ->default('pending')
                  ->after('event_details'); // Ensure it appears after 'event_details' column or adjust as necessary
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_quotes', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
