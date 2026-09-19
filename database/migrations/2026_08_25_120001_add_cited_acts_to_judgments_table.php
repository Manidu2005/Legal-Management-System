<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('judgments', function (Blueprint $table) {
            $table->json('cited_acts')->nullable()->after('summary');
        });
    }

    public function down(): void
    {
        Schema::table('judgments', function (Blueprint $table) {
            $table->dropColumn('cited_acts');
        });
    }
};
