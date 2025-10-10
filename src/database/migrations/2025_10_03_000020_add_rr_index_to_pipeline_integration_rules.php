<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pipeline_integration_rules', function (Blueprint $table) {
            $table->unsignedBigInteger('rr_index')->default(0)->after('action');
        });
    }

    public function down()
    {
        Schema::table('pipeline_integration_rules', function (Blueprint $table) {
            $table->dropColumn('rr_index');
        });
    }
};



