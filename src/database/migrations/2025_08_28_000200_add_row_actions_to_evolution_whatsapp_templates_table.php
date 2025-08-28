<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('evolution_whatsapp_templates', function (Blueprint $table) {
            $table->json('row_actions')->nullable()->after('payload');
        });
    }

    public function down()
    {
        Schema::table('evolution_whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn('row_actions');
        });
    }
};



