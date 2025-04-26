<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('hiring_requests', function (Blueprint $table) {
            $table->decimal('budget', 10, 2)->nullable()->after('type_of_hiring');
        });
    }

    public function down()
    {
        Schema::table('hiring_requests', function (Blueprint $table) {
            $table->dropColumn('budget');
        });
    }
};
