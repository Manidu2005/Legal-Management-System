<?php

namespace App\Exceptions;

use RuntimeException;

class GeminiQuotaExceededException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = false,
        public readonly ?float $retryAfterSeconds = null,
        public readonly ?string $quotaId = null,
    ) {
        parent::__construct($message);
    }

    public static function fromApiBody(string $body): ?self
    {
        if (! str_contains($body, 'RESOURCE_EXHAUSTED') && ! str_contains($body, '"code": 429') && ! str_contains($body, '"code":429')) {
            return null;
        }

        $retryAfter = null;
        if (preg_match('/"retryDelay"\s*:\s*"([0-9.]+)s"/i', $body, $matches)) {
            $retryAfter = (float) $matches[1];
        } elseif (preg_match('/retry in ([0-9.]+)s/i', $body, $matches)) {
            $retryAfter = (float) $matches[1];
        }

        $quotaId = null;
        if (preg_match('/"quotaId"\s*:\s*"([^"]+)"/i', $body, $matches)) {
            $quotaId = $matches[1];
        }

        // Gemini's own quotaId says explicitly whether this is a per-day cap or
        // a per-minute/per-second rate limit — trust that over the retryDelay
        // hint, which Google's own docs/forum confirm can say "retry in 1s"
        // even for a fully-exhausted DAILY quota (waiting that long never
        // helps; only the next daily reset, or a genuinely separate project's
        // key, does).
        if ($quotaId !== null && stripos($quotaId, 'PerDay') !== false) {
            $retryable = false;
        } elseif ($quotaId !== null && (stripos($quotaId, 'PerMinute') !== false || stripos($quotaId, 'PerSecond') !== false)) {
            $retryable = $retryAfter !== null && $retryAfter > 0 && $retryAfter <= 120;
        } else {
            // No quotaId in the body (older-format error) — fall back to the
            // retryDelay heuristic alone.
            $retryable = $retryAfter !== null && $retryAfter > 0 && $retryAfter <= 120;
        }

        $message = $retryable
            ? "Gemini rate limit hit. Retrying after {$retryAfter}s."
            : 'Gemini free-tier daily quota exhausted'.($quotaId ? " ({$quotaId})" : '').'. Resets at midnight Pacific time — resume this import after that, or add a key from a separate Google account/project.';

        return new self($message, $retryable, $retryAfter, $quotaId);
    }
}
