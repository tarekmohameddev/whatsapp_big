<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pipeline_integration_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('integration_id')->index();
            $table->string('name', 255);
            $table->string('match_path', 255)->comment('Dot path in payload to evaluate');
            $table->string('operator', 32)->default('equals')->comment('equals|in|not_equals|exists');
            $table->string('value', 255)->nullable()->comment('comparison value for equals/not_equals or CSV for in');
            $table->json('action')->nullable()->comment('{"method":"cloud_api|evolution_api","gateway_ids":[...],"template_id":123}');
            $table->unsignedInteger('priority')->default(100);
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();

            $table->foreign('integration_id')->references('id')->on('pipeline_integrations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pipeline_integration_rules');
    }
};


