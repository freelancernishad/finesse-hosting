<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('job_seekers', function (Blueprint $table) {
            $table->id();
            $table->string('member_id')->unique();
            $table->string('id_no')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('location')->nullable();
            $table->date('join_date')->nullable();
            $table->string('resume')->nullable(); // Resume file path (PDF/Image)
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_seekers');
    }
};

