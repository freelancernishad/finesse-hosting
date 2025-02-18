<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_categories', function (Blueprint $table) {
            $table->id(); // Use category_id as primary key
            $table->string('category_id')->nullable(); // Use category_id as primary key
            $table->string('name')->unique(); // Unique category name
            $table->enum('status', ['active', 'inactive'])->default('active'); // Status field
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_categories');
    }
}
