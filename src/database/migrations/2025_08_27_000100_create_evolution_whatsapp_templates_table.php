<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('evolution_whatsapp_templates', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement();
            $table->string('uid', 100)->nullable()->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name', 255);
            $table->enum('type', ['simple_txt', 'image', 'poll', 'list_buttons']);
            $table->json('payload');
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();


        });
    }

    public function down()
    {
        Schema::dropIfExists('evolution_whatsapp_templates');
    }
};


