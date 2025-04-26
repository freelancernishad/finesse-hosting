<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHiringRequestIdToReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('request_quote_id')->nullable()->after('reviewer_type'); // Ensure after 'reviewer_type' or adjust as necessary
            $table->foreign('request_quote_id')->references('id')->on('hiring_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['request_quote_id']);
            $table->dropColumn('request_quote_id');
        });
    }
}
