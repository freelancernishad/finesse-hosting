<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('post_job_id')->nullable()->after('job_category_id');
            $table->foreign('post_job_id')->references('id')->on('post_jobs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
             $table->dropColumn('post_job_id');
        });
    }
};
