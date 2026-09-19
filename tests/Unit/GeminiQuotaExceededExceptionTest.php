<?php

namespace Tests\Unit;

use App\Exceptions\GeminiQuotaExceededException;
use Tests\TestCase;

class GeminiQuotaExceededExceptionTest extends TestCase
{
    public function test_per_day_quota_id_is_never_retryable_even_with_a_short_retry_delay(): void
    {
        // Google's own docs/forum confirm the RetryInfo.retryDelay hint can be
        // misleadingly short (e.g. "1s") even when the real violation is the
        // daily cap. The quotaId is the authoritative signal.
        $body = json_encode([
            'error' => [
                'code' => 429,
                'status' => 'RESOURCE_EXHAUSTED',
                'message' => 'You exceeded your current quota.',
                'details' => [
                    [
                        '@type' => 'type.googleapis.com/google.rpc.QuotaFailure',
                        'violations' => [[
                            'quotaId' => 'GenerateRequestsPerDayPerProjectPerModel-FreeTier',
                            'quotaMetric' => 'generativelanguage.googleapis.com/generate_content_free_tier_requests',
                        ]],
                    ],
                    [
                        '@type' => 'type.googleapis.com/google.rpc.RetryInfo',
                        'retryDelay' => '1s',
                    ],
                ],
            ],
        ]);

        $exception = GeminiQuotaExceededException::fromApiBody($body);

        $this->assertNotNull($exception);
        $this->assertFalse($exception->retryable);
        $this->assertSame('GenerateRequestsPerDayPerProjectPerModel-FreeTier', $exception->quotaId);
        $this->assertStringContainsString('daily quota exhausted', $exception->getMessage());
    }

    public function test_per_minute_quota_id_with_short_retry_delay_is_retryable(): void
    {
        $body = json_encode([
            'error' => [
                'code' => 429,
                'status' => 'RESOURCE_EXHAUSTED',
                'message' => 'You exceeded your current quota.',
                'details' => [
                    [
                        '@type' => 'type.googleapis.com/google.rpc.QuotaFailure',
                        'violations' => [[
                            'quotaId' => 'GenerateRequestsPerMinutePerProjectPerModel-FreeTier',
                        ]],
                    ],
                    [
                        '@type' => 'type.googleapis.com/google.rpc.RetryInfo',
                        'retryDelay' => '45s',
                    ],
                ],
            ],
        ]);

        $exception = GeminiQuotaExceededException::fromApiBody($body);

        $this->assertNotNull($exception);
        $this->assertTrue($exception->retryable);
        $this->assertSame(45.0, $exception->retryAfterSeconds);
    }

    public function test_missing_quota_id_falls_back_to_retry_delay_heuristic(): void
    {
        $body = '{"error":{"code":429,"status":"RESOURCE_EXHAUSTED","message":"Please retry in 5s."}}';

        $exception = GeminiQuotaExceededException::fromApiBody($body);

        $this->assertNotNull($exception);
        $this->assertTrue($exception->retryable);
        $this->assertSame(5.0, $exception->retryAfterSeconds);
        $this->assertNull($exception->quotaId);
    }

    public function test_non_quota_body_returns_null(): void
    {
        $this->assertNull(GeminiQuotaExceededException::fromApiBody('{"error":{"code":500,"message":"boom"}}'));
    }
}
