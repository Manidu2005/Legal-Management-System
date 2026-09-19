<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('case_judgment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_case_id')
                ->constrained('legal_cases')
                ->cascadeOnDelete();
            $table->foreignId('judgment_id')
                ->constrained('judgments')
                ->cascadeOnDelete();
            $table->text('relevance_note')->nullable();
            $table->foreignId('added_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['legal_case_id', 'judgment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_judgment');
    }
};
