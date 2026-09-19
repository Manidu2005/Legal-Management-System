<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('judgments', function (Blueprint $table) {
            $table->string('source_hash', 64)->nullable()->after('uploaded_by');
            $table->unsignedInteger('source_start_page')->nullable()->after('source_hash');
            $table->unsignedInteger('source_end_page')->nullable()->after('source_start_page');

            $table->unique(
                ['source_hash', 'source_start_page', 'source_end_page'],
                'judgments_source_fingerprint_unique'
            );
        });

        Schema::create('judgment_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_hash', 64)->index();
            $table->string('source_pdf_path');
            $table->string('original_filename')->nullable();
            $table->string('category');
            $table->string('status'); // awaiting_review|processing|paused|completed|cancelled
            $table->text('pause_reason')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('judgment_import_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained('judgment_import_batches')
                ->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('citation')->nullable();
            $table->string('court')->nullable();
            $table->date('decided_date')->nullable();
            $table->unsignedInteger('start_page');
            $table->unsignedInteger('end_page');
            $table->json('cited_acts')->nullable();
            $table->boolean('include')->default(true);
            $table->string('status'); // pending|processing|completed|skipped|failed
            $table->text('last_error')->nullable();
            $table->foreignId('judgment_id')
                ->nullable()
                ->constrained('judgments')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['batch_id', 'start_page', 'end_page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judgment_import_items');
        Schema::dropIfExists('judgment_import_batches');

        Schema::table('judgments', function (Blueprint $table) {
            $table->dropUnique('judgments_source_fingerprint_unique');
            $table->dropColumn(['source_hash', 'source_start_page', 'source_end_page']);
        });
    }
};
