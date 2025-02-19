<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('job_category_id')->nullable(); // Correct type for foreign key reference
            $table->foreign('job_category_id')->references('id')->on('job_categories')->onDelete('set null'); // Foreign key constraint
        });
    }

    public function down()
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            $table->dropForeign(['job_category_id']);
            $table->dropColumn('job_category_id');
        });
    }
};

