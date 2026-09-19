<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class LegalCase extends Model
{
    use HasFactory;

    protected $table = 'legal_cases';

    protected $fillable = [
        'client_id',
        'name',
        'assigned_attorney_id',
        'case_category_id',
        'court_id',
        'applicable_law',
        'case_type_other',
        'status',
    ];

    /**
     * access_code_hash is intentionally excluded from mass assignment.
     * It may only be changed via setAccessCode(), never via create()/update().
     */
    protected $hidden = [
        'access_code_hash',
    ];

    /**
     * Valid status values for this model.
     */
    public const STATUSES = [
        'pending',
        'active',
        'trial_scheduled',
        'judgment_delivered',
        'case_closed',
    ];

    /**
     * Status values that trigger client notification (FR-4.1).
     */
    public const MILESTONE_STATUSES = [
        'trial_scheduled',
        'judgment_delivered',
        'case_closed',
    ];

    /**
     * Which personal-law system governs this case (Part 5 of the Sri Lankan
     * case-type reference). Only meaningful for Family / Property /
     * Succession matters — nullable/optional for everything else.
     */
    public const APPLICABLE_LAW_GENERAL = 'general';
    public const APPLICABLE_LAW_KANDYAN = 'kandyan';
    public const APPLICABLE_LAW_THESAWALAMAI = 'thesawalamai';
    public const APPLICABLE_LAW_MUSLIM = 'muslim';

    public const APPLICABLE_LAWS = [
        self::APPLICABLE_LAW_GENERAL => 'General Law',
        self::APPLICABLE_LAW_KANDYAN => 'Kandyan Law',
        self::APPLICABLE_LAW_THESAWALAMAI => 'Thesawalamai',
        self::APPLICABLE_LAW_MUSLIM => 'Muslim Law',
    ];

    /**
     * Human-readable label: custom name, or "{client} — {case category}".
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(function (): string {
            if (! empty($this->name)) {
                return $this->name;
            }

            $clientName = $this->client?->name ?? 'Unknown Client';
            $caseType = $this->caseCategory?->name ?? $this->case_type_other ?? 'General';

            return "{$clientName} — {$caseType}";
        });
    }

    /**
     * The specific case-type leaf (level 3) this case is filed under.
     */
    public function caseCategory(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'case_category_id');
    }

    /**
     * The court/forum this case is being heard in.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class, 'court_id');
    }

    /**
     * The client this case belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The attorney assigned to this case.
     */
    public function assignedAttorney(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_attorney_id');
    }

    /**
     * Court dates for this case.
     */
    public function courtDates(): HasMany
    {
        return $this->hasMany(CourtDate::class, 'case_id');
    }

    /**
     * Documents associated with this case.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'case_id');
    }

    /**
     * Ledger entries for this case.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'case_id');
    }

    /**
     * Research notes for this case.
     */
    public function researchNotes(): HasMany
    {
        return $this->hasMany(ResearchNote::class, 'case_id');
    }

    /**
     * Judgments attached to this case from the firm-wide library.
     */
    public function judgments(): BelongsToMany
    {
        return $this->belongsToMany(Judgment::class, 'case_judgment', 'legal_case_id', 'judgment_id')
            ->withPivot(['id', 'relevance_note', 'added_by'])
            ->withTimestamps();
    }

    /**
     * Pivot rows linking this case to judgments.
     */
    public function caseJudgments(): HasMany
    {
        return $this->hasMany(CaseJudgment::class, 'legal_case_id');
    }

    /**
     * Count of trial dates for fee calculation (FR-3.1).
     */
    public function trialDateCount(): int
    {
        return $this->courtDates()->where('type', 'trial_date')->count();
    }

    /**
     * Calculate total appearance fee: trial_date count × attorney rate (FR-3.1).
     */
    public function totalAppearanceFee(): float
    {
        return $this->trialDateCount() * ($this->assignedAttorney->flat_appearance_rate ?? 0);
    }

    /**
     * Whether this case has an access code configured.
     */
    public function hasAccessCode(): bool
    {
        return ! empty($this->access_code_hash);
    }

    /**
     * Set (or change) the case's access code. The raw code is hashed
     * immediately and never persisted or logged in plain text.
     */
    public function setAccessCode(string $code): void
    {
        $this->access_code_hash = Hash::make($code);
        $this->save();
    }

    /**
     * Verify a raw code against the stored hash.
     */
    public function verifyAccessCode(string $code): bool
    {
        return $this->hasAccessCode() && Hash::check($code, $this->access_code_hash);
    }
}
