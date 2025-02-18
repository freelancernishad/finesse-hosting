<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('applied_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('post_code')->nullable();
            $table->text('address')->nullable();
            $table->string('interest_file')->nullable(); // File path
            $table->string('area')->nullable();
            $table->string('category')->nullable();
            $table->foreignId('job_seeker_id')->nullable()->constrained('job_seekers')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('applied_jobs');
    }
};
