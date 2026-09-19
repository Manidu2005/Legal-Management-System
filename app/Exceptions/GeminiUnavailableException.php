<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Gemini could not be reached at all (DNS, TLS, timeout, no route). Extra API
 * keys cannot help — the host never answered — so the import should pause
 * instead of marking every remaining case as failed.
 */
class GeminiUnavailableException extends RuntimeException
{
}
