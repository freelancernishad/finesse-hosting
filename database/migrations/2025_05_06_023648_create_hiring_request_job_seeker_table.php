<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHiringRequestJobSeekerTable extends Migration
{
    public function up()
    {
        Schema::create('hiring_request_job_seeker', function (Blueprint $table) {
            $table->engine = 'InnoDB'; // Enforce engine that supports FKs
            $table->id();

            $table->unsignedBigInteger('hiring_request_id');
            $table->unsignedBigInteger('job_seeker_id');

            $table->decimal('hourly_rate', 10, 2);
            $table->integer('total_hours');
            $table->decimal('total_amount', 12, 2);

            $table->timestamps();

            $table->unique(['hiring_request_id', 'job_seeker_id']);

            // $table->foreign('hiring_request_id')->references('id')->on('hiring_requests')->onDelete('cascade');
            // $table->foreign('job_seeker_id')->references('id')->on('job_seekers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hiring_request_job_seeker');
    }
}
