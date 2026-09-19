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
        Schema::create('research_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')
                ->constrained('legal_cases')
                ->cascadeOnDelete();
            $table->string('category');
            $table->string('citation');
            $table->string('court_or_source')->nullable();
            $table->text('note');
            $table->string('source_url')->nullable();
            $table->foreignId('added_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_notes');
    }
};
