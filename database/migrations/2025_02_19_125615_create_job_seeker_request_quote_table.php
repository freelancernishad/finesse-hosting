<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobSeekerRequestQuoteTable extends Migration
{
    public function up()
    {
        Schema::create('job_seeker_request_quote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_seeker_id')->constrained('job_seekers')->onDelete('cascade');
            $table->foreignId('request_quote_id')->constrained('hiring_requests')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_seeker_request_quote');
    }
}
