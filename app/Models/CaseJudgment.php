<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseJudgment extends Model
{
    protected $table = 'case_judgment';

    protected $fillable = [
        'legal_case_id',
        'judgment_id',
        'relevance_note',
        'added_by',
    ];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }

    public function judgment(): BelongsTo
    {
        return $this->belongsTo(Judgment::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
