<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JudgmentImportItem extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'batch_id',
        'position',
        'citation',
        'court',
        'decided_date',
        'start_page',
        'end_page',
        'cited_acts',
        'include',
        'status',
        'last_error',
        'judgment_id',
    ];

    protected function casts(): array
    {
        return [
            'decided_date' => 'date',
            'cited_acts' => 'array',
            'include' => 'boolean',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(JudgmentImportBatch::class, 'batch_id');
    }

    public function judgment(): BelongsTo
    {
        return $this->belongsTo(Judgment::class);
    }
}
