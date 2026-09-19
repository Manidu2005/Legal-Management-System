<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sri Lankan forum/court map — from the Supreme Court down to village
 * Mediation Boards and regulatory commissions (Part 1 of the case-type
 * reference: which body hears what).
 */
class Court extends Model
{
    protected $fillable = [
        'name',
        'tier',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public const TIER_APEX = 'apex';
    public const TIER_SUPERIOR_APPELLATE = 'superior_appellate';
    public const TIER_SUPERIOR_ORIGINAL = 'superior_original';
    public const TIER_FIRST_INSTANCE_CIVIL = 'first_instance_civil';
    public const TIER_FIRST_INSTANCE_CRIMINAL = 'first_instance_criminal';
    public const TIER_LOCALIZED = 'localized';
    public const TIER_PERSONAL_LAW = 'personal_law';
    public const TIER_ADR = 'adr';
    public const TIER_REGULATORY = 'regulatory';

    public const TIERS = [
        self::TIER_APEX => 'Apex',
        self::TIER_SUPERIOR_APPELLATE => 'Superior Appellate',
        self::TIER_SUPERIOR_ORIGINAL => 'Superior Original',
        self::TIER_FIRST_INSTANCE_CIVIL => 'First Instance (Civil)',
        self::TIER_FIRST_INSTANCE_CRIMINAL => 'First Instance (Criminal)',
        self::TIER_LOCALIZED => 'Localized / Minor',
        self::TIER_PERSONAL_LAW => 'Personal-Law Forum',
        self::TIER_ADR => 'Non-Judicial / ADR',
        self::TIER_REGULATORY => 'Regulatory / Quasi-Judicial',
    ];

    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'court_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
