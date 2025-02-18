<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeBusinessNameColumnToJsonInUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Ensure column exists before modifying it
            if (!Schema::hasColumn('users', 'business_name')) {
                $table->text('business_name')->nullable();
            }
        });

        // Ensure all existing values are valid JSON or set a default value
        DB::statement('UPDATE users SET business_name = "[]" WHERE business_name IS NULL OR JSON_VALID(business_name) = 0');

        Schema::table('users', function (Blueprint $table) {
            // Modify the column to JSON type
            DB::statement('ALTER TABLE users MODIFY COLUMN business_name JSON NULL');
        });
    }

}
