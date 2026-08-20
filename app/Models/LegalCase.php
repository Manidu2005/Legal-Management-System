<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'case_type',
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
     * Valid case_type values for this model.
     */
    public const CASE_TYPES = [
        'Civil Litigation',
        'Property Dispute',
        'Criminal Defence',
        'Family Law',
        'Labour Dispute',
        'Land Acquisition',
        'Other',
    ];

    /**
     * Human-readable label: custom name, or "{client} — {case_type}".
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(function (): string {
            if (! empty($this->name)) {
                return $this->name;
            }

            $clientName = $this->client?->name ?? 'Unknown Client';
            $caseType = $this->case_type ?? 'General';

            return "{$clientName} — {$caseType}";
        });
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
