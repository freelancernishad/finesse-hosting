<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_seeker_id')->nullable()->constrained('job_seekers')->onDelete('set null'); // Reviewed JobSeeker
            $table->foreignId('applied_job_id')->nullable()->constrained('applied_jobs')->onDelete('cascade'); // Related Job Application
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null'); // Who wrote the review (nullable)

            // Additional reviewer details (if no registered user)
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_email')->nullable();
            $table->string('reviewer_phone')->nullable();

            $table->integer('rating')->default(1); // Rating (1-5 scale)
            $table->text('comment')->nullable(); // Review comment
            $table->string('title')->nullable(); // Review title
            $table->string('reviewer_type')->nullable(); // Can store 'Employer' or 'JobSeeker'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};


