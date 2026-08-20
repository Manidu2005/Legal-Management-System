<?php

namespace App\Policies;

use App\Models\LegalCase;
use App\Models\User;

class CaseAccessPolicy
{
    /**
     * Determine whether the user can view the given case.
     *
     * - partner: unrestricted access to all cases.
     * - associate: only cases assigned to them.
     * - clerk: only if the case has an access code configured AND the
     *   clerk has already verified it for this case in the current
     *   session (see CaseAccessCodeController).
     */
    public function view(User $user, LegalCase $case): bool
    {
        return match ($user->role) {
            'partner' => true,
            'associate' => $case->assigned_attorney_id === $user->id,
            'clerk' => $case->hasAccessCode()
                && session()->get("case_access_verified.{$case->id}") === true,
            default => false,
        };
    }

    /**
     * Determine whether the user can set or change the case's access code.
     *
     * Deliberately separate from view(): once a clerk verifies a code,
     * view() may return true for them, but they must never be able to
     * manage the code itself. Only a partner or the assigned associate can.
     */
    public function manageAccessCode(User $user, LegalCase $case): bool
    {
        return match ($user->role) {
            'partner' => true,
            'associate' => $case->assigned_attorney_id === $user->id,
            default => false,
        };
    }
}
