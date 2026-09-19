<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hierarchical Sri Lankan case-type taxonomy.
     *
     * level 1 = Main Type   (e.g. "Civil", "Criminal", "Constitutional & Public Law")
     * level 2 = Group       (e.g. "Property & Land Law", "Offences Against Property")
     * level 3 = Specific type / leaf (e.g. "Partition actions") — this is the level
     *           that a legal_cases row actually points to via case_category_id.
     */
    public function up(): void
    {
        Schema::create('case_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('case_categories')->nullOnDelete();
            $table->unsignedTinyInteger('level'); // 1, 2, or 3
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['parent_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_categories');
    }
};
