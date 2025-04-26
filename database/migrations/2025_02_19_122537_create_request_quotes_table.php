<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('request_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relate to user
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('how_did_you_hear')->nullable();
            $table->date('event_date')->nullable();
            $table->time('start_time')->nullable();
            $table->json('categories')->nullable(); // Multiple job categories with counts
            $table->integer('number_of_guests')->nullable();
            $table->string('event_location')->nullable();
            $table->text('event_details')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('request_quotes');
    }
};
