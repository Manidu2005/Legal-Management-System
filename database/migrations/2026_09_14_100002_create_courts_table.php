<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sri Lankan forum/court map (Part 1 of the case-type reference):
     * Supreme Court down to Mediation Boards and regulatory commissions.
     */
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tier'); // see App\Models\Court::TIERS
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
