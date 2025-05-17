<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hiring_consultation_requests', function (Blueprint $table) {
            $table->id();
             $table->unsignedBigInteger('user_id');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('industry_sector')->nullable();
            $table->string('company_size')->nullable();
            $table->text('company_description')->nullable();
            $table->text('hiring_needs');
            $table->string('number_of_positions');
            $table->string('hiring_urgency');
            $table->date('preferred_consultation_date')->nullable();
            $table->text('additional_info')->nullable();
            
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hiring_consultation_requests');
    }
};
