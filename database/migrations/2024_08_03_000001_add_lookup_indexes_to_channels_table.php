<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->index(['name', 'value'], 'channels_name_value_index');
            $table->index(['model_type', 'model_id', 'name'], 'channels_model_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropIndex('channels_name_value_index');
            $table->dropIndex('channels_model_name_index');
        });
    }
};