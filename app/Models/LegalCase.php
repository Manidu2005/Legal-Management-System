<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalCase extends Model
{
    use HasFactory;

    protected $table = 'legal_cases';

    protected $fillable = [
        'client_id',
        'assigned_attorney_id',
        'case_type',
        'status',
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
}
