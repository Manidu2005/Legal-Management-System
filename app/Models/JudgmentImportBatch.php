<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JudgmentImportBatch extends Model
{
    public const STATUS_AWAITING_REVIEW = 'awaiting_review';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'source_hash',
        'source_pdf_path',
        'original_filename',
        'category',
        'status',
        'pause_reason',
        'uploaded_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(JudgmentImportItem::class, 'batch_id')->orderBy('position');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [
            self::STATUS_AWAITING_REVIEW,
            self::STATUS_PROCESSING,
            self::STATUS_PAUSED,
        ], true);
    }

    public function pendingItemsCount(): int
    {
        return $this->items()
            ->where('include', true)
            ->whereIn('status', [
                JudgmentImportItem::STATUS_PENDING,
                JudgmentImportItem::STATUS_FAILED,
            ])
            ->count();
    }

    public function completedItemsCount(): int
    {
        return $this->items()
            ->whereIn('status', [
                JudgmentImportItem::STATUS_COMPLETED,
                JudgmentImportItem::STATUS_SKIPPED,
            ])
            ->count();
    }
}
