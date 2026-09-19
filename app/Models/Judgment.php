<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Judgment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'court',
        'decided_date',
        'pdf_path',
        'summary',
        'cited_acts',
        'embedding',
        'uploaded_by',
        'source_hash',
        'source_start_page',
        'source_end_page',
        'external_id',
        'source_dataset',
        'source_url',
    ];

    /** Allowed upload file types — PDF only. */
    public const ALLOWED_FILE_TYPES = ['pdf'];

    /** Maximum upload size in kilobytes — 25 MB (same cap as Document). */
    public const MAX_FILE_SIZE_KB = 25600;

    /** Valid judgment library categories. */
    public const CATEGORIES = ['judgment', 'act_or_ordinance', 'other'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decided_date' => 'date',
            'embedding' => 'array',
            'cited_acts' => 'array',
        ];
    }

    /**
     * The user who uploaded this judgment.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Cases this judgment has been attached to.
     */
    public function legalCases(): BelongsToMany
    {
        return $this->belongsToMany(LegalCase::class, 'case_judgment', 'judgment_id', 'legal_case_id')
            ->withPivot(['id', 'relevance_note', 'added_by'])
            ->withTimestamps();
    }

    public function hasLocalPdf(): bool
    {
        return filled($this->pdf_path);
    }

    public function sourceLabel(): ?string
    {
        return match ($this->source_dataset) {
            'navod_sri_lanka_case_law' => 'Sri Lanka Case Law Dataset',
            'nuuuwan_appeal_court' => 'Court of Appeal dataset',
            'nuuuwan_supreme_court' => 'Supreme Court dataset',
            default => $this->source_dataset,
        };
    }
}
