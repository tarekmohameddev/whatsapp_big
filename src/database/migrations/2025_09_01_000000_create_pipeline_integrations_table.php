<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pipeline_integrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uid', 100)->unique();
            $table->unsignedInteger('user_id')->index();
            $table->string('name', 255);
            $table->string('webhook_secret', 255);
            $table->string('phone_path', 255)->comment('Dot path to recipient phone in incoming JSON');
            $table->json('allowed_methods')->nullable()->comment('{"cloud_api": true, "evolution_api": true}');
            $table->json('allowed_gateways')->nullable()->comment('{"cloud_api": [ids], "evolution_api": [ids]}');
            $table->json('defaults')->nullable()->comment('default method/template/gateway and mappings');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pipeline_integrations');
    }
};


