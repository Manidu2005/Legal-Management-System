<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseAccessCodeController extends Controller
{
    /**
     * Show the code-entry form for a clerk to unlock a case for this session.
     */
    public function show(Request $request, LegalCase $case): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'clerk'
            || ! $case->hasAccessCode()
            || session()->get("case_access_verified.{$case->id}") === true) {
            // Nothing to verify for this user/case — send them into the
            // normal flow, which will authorize (or 403) as appropriate.
            return redirect()->route('cases.show', $case);
        }

        return view('cases.access-code', compact('case'));
    }

    /**
     * Verify a submitted access code and, on success, mark the case as
     * verified for the remainder of this session.
     */
    public function store(Request $request, LegalCase $case): RedirectResponse
    {
        abort_unless($request->user()->role === 'clerk', 403);
        abort_unless($case->hasAccessCode(), 403);

        $validated = $request->validate([
            'access_code' => ['required', 'string'],
        ]);

        if (! $case->verifyAccessCode($validated['access_code'])) {
            return back()->withErrors([
                'access_code' => 'Incorrect access code.',
            ]);
        }

        session()->put("case_access_verified.{$case->id}", true);

        $redirectTo = $request->session()->pull("case_access_intended.{$case->id}")
            ?? route('cases.show', $case);

        return redirect()->to($redirectTo)->with('success', 'Access code verified.');
    }

    /**
     * Set or change the case's access code. Restricted to a partner or
     * the associate assigned to this specific case.
     */
    public function update(Request $request, LegalCase $case): RedirectResponse
    {
        $this->authorize('manageAccessCode', $case);

        $validated = $request->validate([
            'access_code' => ['required', 'string', 'min:4', 'max:50'],
        ]);

        $case->setAccessCode($validated['access_code']);

        return redirect()->route('cases.edit', $case)->with('success', 'Access code updated.');
    }
}
