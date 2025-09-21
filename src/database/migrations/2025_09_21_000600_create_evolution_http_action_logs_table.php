<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_http_action_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 64)->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('template_id')->index();
            $table->unsignedBigInteger('gateway_id')->nullable()->index();
            $table->string('selected_row_id');
            $table->string('method', 10);
            $table->text('url');
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->string('sender')->nullable();
            $table->string('customer')->nullable();
            $table->json('context_meta')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'template_id', 'selected_row_id'], 'ehal_user_tpl_row_idx');
            $table->index(['user_id', 'created_at'], 'ehal_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_http_action_logs');
    }
};


