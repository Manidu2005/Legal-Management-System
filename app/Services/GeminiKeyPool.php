<?php

namespace App\Services;

use App\Exceptions\GeminiQuotaExceededException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Rotates across one or more Gemini API keys so a single key's free-tier
 * quota running out doesn't stop indexing — the next configured key is
 * tried automatically before the import pauses for the day.
 *
 * Configure the primary key via GEMINI_API_KEY and any extra fallback keys
 * via GEMINI_API_KEYS (comma or newline separated) in .env.
 *
 * IMPORTANT: Gemini free-tier quotas are enforced per Google Cloud PROJECT,
 * not per API key string. Extra keys only give genuinely extra capacity if
 * they belong to separate projects/Google accounts — keys generated under
 * the same account typically share one quota pool, so rotating between them
 * will not avoid a shared daily cap or rate limit.
 *
 * The "current key" pointer and any known-exhausted-for-today keys are
 * stored in the app cache so this survives across requests (each web
 * request is its own PHP process).
 */
class GeminiKeyPool
{
    private const CACHE_KEY = 'gemini_api_key_pool_index';

    private const EXHAUSTED_CACHE_PREFIX = 'gemini_api_key_pool_exhausted_';

    /** @var list<string> */
    private readonly array $keys;

    public function __construct()
    {
        $this->keys = $this->loadKeys();
    }

    public function count(): int
    {
        return count($this->keys);
    }

    public function current(): string
    {
        if ($this->keys === []) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        return $this->keys[$this->currentIndex()];
    }

    /**
     * Run $callback with the current API key.
     *
     * - A short, per-minute rate limit (retryable, has a retryDelay) is
     *   waited out and retried on the SAME key first — Gemini's own
     *   RetryInfo is the most reliable signal that this exact window will
     *   clear, and hammering every other key instantly tends to look like a
     *   burst and can re-trigger the same limit on all of them at once.
     * - Only a non-retryable (daily-cap) quota error, or a retryable one
     *   that still fails after waiting, causes a rotation to the next key.
     *
     * Rethrows the last error once every key has been tried (and, where
     * applicable, waited out) so the caller can pause/report as usual.
     *
     * @template T
     * @param  callable(string $apiKey): T  $callback
     * @return T
     */
    public function run(callable $callback): mixed
    {
        if ($this->keys === []) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $maxAttempts = count($this->keys);
        $lastException = null;

        // If every configured key is already known to be daily-exhausted
        // (learned from an earlier call in this or a prior request), don't
        // waste time dialing each one again just to get the same answer —
        // fail fast with a clear, aggregated message.
        if ($this->allKnownExhausted()) {
            throw new GeminiQuotaExceededException(
                'All '.$maxAttempts.' configured Gemini key(s) already hit their daily quota today. '
                .'Resets at midnight Pacific time. Resume this import after that.'
                .($maxAttempts > 1 ? ' If these keys belong to the same Google account/project, they share one quota pool — keys from separate projects would give real extra capacity.' : ''),
                retryable: false,
            );
        }

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $index = $this->currentIndex();
            $key = $this->current();

            if ($this->isMarkedExhausted($index)) {
                if ($attempt < $maxAttempts) {
                    $this->rotate();
                }

                continue;
            }

            try {
                return $callback($key);
            } catch (GeminiQuotaExceededException $e) {
                $lastException = $e;
                $this->logQuotaException($index, $e);

                if (! $e->retryable) {
                    // Daily-cap error — this key is done for today; remember
                    // that so future calls (including a later Resume click)
                    // skip it instantly instead of re-waiting/re-asking.
                    $this->markExhausted($index);
                } elseif ($e->retryAfterSeconds) {
                    sleep((int) ceil($e->retryAfterSeconds) + 1);

                    try {
                        return $callback($key);
                    } catch (GeminiQuotaExceededException $waitException) {
                        $lastException = $waitException;
                        $this->logQuotaException($index, $waitException);

                        if (! $waitException->retryable) {
                            $this->markExhausted($index);
                        }
                    }
                }
            }

            if ($attempt < $maxAttempts) {
                $this->rotate();
            }
        }

        throw $lastException instanceof Throwable
            ? $lastException
            : new RuntimeException('Gemini request failed.');
    }

    public function rotate(): void
    {
        if (count($this->keys) <= 1) {
            return;
        }

        $next = ($this->currentIndex() + 1) % count($this->keys);
        Cache::forever(self::CACHE_KEY, $next);
    }

    private function logQuotaException(int $index, GeminiQuotaExceededException $e): void
    {
        Log::warning('Gemini quota/rate-limit response', [
            'key_index' => $index + 1,
            'key_count' => count($this->keys),
            'retryable' => $e->retryable,
            'retry_after_seconds' => $e->retryAfterSeconds,
            'quota_id' => $e->quotaId,
            'message' => $e->getMessage(),
        ]);
    }

    private function markExhausted(int $index): void
    {
        Cache::put(self::EXHAUSTED_CACHE_PREFIX.$index, true, $this->secondsUntilPacificMidnight());
    }

    private function isMarkedExhausted(int $index): bool
    {
        return (bool) Cache::get(self::EXHAUSTED_CACHE_PREFIX.$index, false);
    }

    private function allKnownExhausted(): bool
    {
        foreach (array_keys($this->keys) as $index) {
            if (! $this->isMarkedExhausted($index)) {
                return false;
            }
        }

        return true;
    }

    private function secondsUntilPacificMidnight(): int
    {
        $now = Carbon::now('America/Los_Angeles');
        $nextMidnight = $now->copy()->startOfDay()->addDay();

        return max(60, $now->diffInSeconds($nextMidnight));
    }

    public function currentIndex(): int
    {
        $index = (int) Cache::get(self::CACHE_KEY, 0);

        if ($index < 0 || $index >= count($this->keys)) {
            $index = 0;
        }

        return $index;
    }

    public function status(): string
    {
        $exhausted = $this->exhaustedCount();
        $suffix = $exhausted > 0
            ? sprintf(' (%d of %d exhausted for today)', $exhausted, count($this->keys))
            : '';

        return (count($this->keys) > 1
            ? sprintf('Gemini key %d of %d', $this->currentIndex() + 1, count($this->keys))
            : 'Gemini key 1 of 1').$suffix;
    }

    public function exhaustedCount(): int
    {
        $count = 0;

        foreach (array_keys($this->keys) as $index) {
            if ($this->isMarkedExhausted($index)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return list<string>
     */
    private function loadKeys(): array
    {
        $keys = [];

        $primary = config('services.gemini.key');
        if (is_string($primary) && trim($primary) !== '') {
            $keys[] = trim($primary);
        }

        $extra = config('services.gemini.keys');

        if (is_string($extra) && trim($extra) !== '') {
            foreach (preg_split('/[,\n]+/', $extra) as $candidate) {
                $candidate = trim($candidate);
                if ($candidate !== '' && ! in_array($candidate, $keys, true)) {
                    $keys[] = $candidate;
                }
            }
        } elseif (is_array($extra)) {
            foreach ($extra as $candidate) {
                $candidate = trim((string) $candidate);
                if ($candidate !== '' && ! in_array($candidate, $keys, true)) {
                    $keys[] = $candidate;
                }
            }
        }

        return $keys;
    }
}
