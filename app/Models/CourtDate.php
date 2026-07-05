<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'date',
        'type',
        'reminder_sent',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'reminder_sent' => 'boolean',
        ];
    }

    /**
     * The case this court date belongs to.
     */
    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    /**
     * Check if this is a trial date.
     */
    public function isTrialDate(): bool
    {
        return $this->type === 'trial_date';
    }
}
