<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'file_path',
        'file_type',
        'category',
        'uploaded_by',
    ];

    /** Allowed upload file types (FR-5.1). */
    public const ALLOWED_FILE_TYPES = ['pdf', 'jpg', 'png'];

    /** Maximum upload size in kilobytes — 25 MB (FR-5.1). */
    public const MAX_FILE_SIZE_KB = 25600;

    /** Valid document categories. */
    public const CATEGORIES = ['evidence', 'deeds', 'correspondence'];

    /**
     * The case this document belongs to.
     */
    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    /**
     * The user who uploaded this document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Alias for uploader — used by eager loading in LegalCaseController.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
