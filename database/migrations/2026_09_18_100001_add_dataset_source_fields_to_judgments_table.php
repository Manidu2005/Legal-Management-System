<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('judgments', function (Blueprint $table) {
            $table->string('external_id')->nullable()->unique()->after('source_end_page');
            $table->string('source_dataset', 64)->nullable()->after('external_id');
            $table->text('source_url')->nullable()->after('source_dataset');
        });
    }

    public function down(): void
    {
        Schema::table('judgments', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropColumn(['external_id', 'source_dataset', 'source_url']);
        });
    }
};
