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
        Schema::create('post_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiring_request_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('category');
            $table->string('model')->nullable();
            $table->string('experience')->nullable();
            $table->string('salary_type');
            $table->decimal('min_salary', 10, 2)->nullable();
            $table->decimal('max_salary', 10, 2)->nullable();
            $table->string('location');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'closed', 'draft'])->default('open');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_jobs');
    }
};
