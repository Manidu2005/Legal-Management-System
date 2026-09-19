<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

/**
 * Seeds the Sri Lankan forum/court map (Part 1 of the case-type reference):
 * which body hears what, from the Supreme Court down to village Mediation
 * Boards and regulatory commissions.
 */
class CourtSeeder extends Seeder
{
    public function run(): void
    {
        $courts = [
            Court::TIER_APEX => [
                'Supreme Court',
            ],
            Court::TIER_SUPERIOR_APPELLATE => [
                'Court of Appeal',
            ],
            Court::TIER_SUPERIOR_ORIGINAL => [
                'High Court',
                'Provincial High Court',
                'Commercial High Court',
                'Civil Appellate High Court',
                'High Court of Appeal',
            ],
            Court::TIER_FIRST_INSTANCE_CIVIL => [
                'District Court',
            ],
            Court::TIER_FIRST_INSTANCE_CRIMINAL => [
                'Magistrate\'s Court',
            ],
            Court::TIER_LOCALIZED => [
                'Primary Court',
            ],
            Court::TIER_PERSONAL_LAW => [
                'Quazi Court',
                'Board of Quazis',
            ],
            Court::TIER_ADR => [
                'Mediation Board',
                'Labour Tribunal',
                'Industrial Court',
                'Agrarian Tribunal',
                'Rent Board',
                'Rent Board of Review',
                'Debt Conciliation Board',
            ],
            Court::TIER_REGULATORY => [
                'Tax Appeals Commission',
                'Commission to Investigate Allegations of Bribery or Corruption (CIABOC)',
                'Human Rights Commission of Sri Lanka',
                'Consumer Affairs Authority / Council',
                'Securities and Exchange Commission of Sri Lanka',
                'Election Commission',
                'Land Acquisition Board of Review',
                'Ceiling on Housing Property Board of Review',
            ],
        ];

        $sort = 0;
        foreach ($courts as $tier => $names) {
            foreach ($names as $name) {
                $sort++;
                Court::create([
                    'name' => $name,
                    'tier' => $tier,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]);
            }
        }

        $this->command?->info('✓ Seeded courts: ' . Court::count() . ' courts');
    }
}
