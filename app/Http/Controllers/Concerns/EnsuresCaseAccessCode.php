<?php

namespace App\Http\Controllers\Concerns;

use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;

/**
 * Shared by controllers that expose case-scoped content (cases, documents)
 * to redirect clerks to the access-code entry form instead of a bare 403
 * when the case is code-protected and not yet verified this session.
 */
trait EnsuresCaseAccessCode
{
    /**
     * Returns a redirect response if the current user needs to verify a
     * case access code before proceeding, or null if they can proceed
     * straight to the normal authorization check.
     */
    protected function redirectForCaseAccessCode(LegalCase $case): ?RedirectResponse
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'clerk' || ! $case->hasAccessCode()) {
            // Not a clerk, or no code configured — let the policy decide
            // (a clerk with no code configured for the case is denied outright).
            return null;
        }

        if (session()->get("case_access_verified.{$case->id}") === true) {
            return null;
        }

        session()->put("case_access_intended.{$case->id}", request()->fullUrl());

        return redirect()->route('cases.access-code.show', $case);
    }
}
