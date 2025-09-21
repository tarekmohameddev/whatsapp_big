<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_button_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 64)->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('template_id')->index();
            $table->unsignedBigInteger('gateway_id')->nullable()->index();
            $table->string('selected_row_id');
            $table->string('row_title')->nullable();
            $table->string('row_description')->nullable();
            $table->string('sender')->nullable();
            $table->string('customer')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'template_id', 'selected_row_id'], 'ebc_user_tpl_row_idx');
            $table->index(['user_id', 'created_at'], 'ebc_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_button_clicks');
    }
};


