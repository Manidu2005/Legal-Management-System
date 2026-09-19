<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hierarchical Sri Lankan case-type taxonomy.
 *
 * Level 1 — Main Type (Civil / Criminal / Constitutional & Public Law)
 * Level 2 — Group (e.g. "Property & Land Law", "Offences Against Property")
 * Level 3 — Specific type / leaf (e.g. "Partition actions") — the level a
 *           legal_cases row actually points to via case_category_id.
 */
class CaseCategory extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'level',
        'sort_order',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public const LEVEL_MAIN_TYPE = 1;
    public const LEVEL_GROUP = 2;
    public const LEVEL_SPECIFIC_TYPE = 3;

    public const LEVELS = [
        self::LEVEL_MAIN_TYPE,
        self::LEVEL_GROUP,
        self::LEVEL_SPECIFIC_TYPE,
    ];

    /**
     * The parent category (null for level-1 Main Types).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'parent_id');
    }

    /**
     * Direct child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(CaseCategory::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Cases filed under this (normally leaf) category.
     */
    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'case_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function isLeaf(): bool
    {
        return $this->level === self::LEVEL_SPECIFIC_TYPE;
    }

    /**
     * Human-readable breadcrumb, e.g. "Civil › Property & Land Law › Partition actions".
     */
    public function fullPath(): string
    {
        $names = [$this->name];
        $node = $this;

        while ($node->parent_id !== null) {
            $node = $node->relationLoaded('parent') ? $node->parent : $node->parent()->first();
            if (! $node) {
                break;
            }
            $names[] = $node->name;
        }

        return implode(' › ', array_reverse($names));
    }
}
