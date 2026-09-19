<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'category',
        'citation',
        'court_or_source',
        'note',
        'source_url',
        'added_by',
    ];

    /** Valid research note categories. */
    public const CATEGORIES = ['judgment', 'act_or_ordinance', 'other'];

    /**
     * The case this research note belongs to.
     */
    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    /**
     * The user who added this research note.
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
