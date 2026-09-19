<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the free-text, validation-mismatched `case_type` column with:
     * - case_category_id: FK into the new hierarchical case_categories taxonomy (leaf/level-3 node)
     * - court_id: FK into the new courts (forum map) table
     * - applicable_law: which personal-law system governs (general/kandyan/thesawalamai/muslim)
     * - case_type_other: free-text detail when the leaf category is "Other", and also used to
     *   preserve the legacy case_type value for any pre-existing rows during this migration.
     */
    public function up(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->foreignId('case_category_id')->nullable()->after('case_type')
                ->constrained('case_categories')->nullOnDelete();
            $table->foreignId('court_id')->nullable()->after('case_category_id')
                ->constrained('courts')->nullOnDelete();
            $table->string('applicable_law')->nullable()->after('court_id');
            $table->string('case_type_other')->nullable()->after('applicable_law');
        });

        // Preserve any existing free-text case_type value rather than silently
        // discarding it — it lands in case_type_other, uncategorized, so it can
        // be reviewed and properly recategorized via the case edit screen.
        DB::table('legal_cases')->whereNotNull('case_type')->orderBy('id')->each(function ($case) {
            DB::table('legal_cases')->where('id', $case->id)->update([
                'case_type_other' => $case->case_type,
            ]);
        });

        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropColumn('case_type');
        });
    }

    public function down(): void
    {
        Schema::table('legal_cases', function (Blueprint $table) {
            $table->string('case_type')->nullable()->after('name');
        });

        DB::table('legal_cases')->whereNotNull('case_type_other')->orderBy('id')->each(function ($case) {
            DB::table('legal_cases')->where('id', $case->id)->update([
                'case_type' => $case->case_type_other,
            ]);
        });

        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('case_category_id');
            $table->dropConstrainedForeignId('court_id');
            $table->dropColumn(['applicable_law', 'case_type_other']);
        });
    }
};
