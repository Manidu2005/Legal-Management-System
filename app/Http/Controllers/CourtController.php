<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourtRequest;
use App\Http\Requests\UpdateCourtRequest;
use App\Models\Court;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CourtController extends Controller
{
    /**
     * Display the courts/forum list, grouped by tier (partner only).
     */
    public function index(): View
    {
        Gate::authorize('manage-taxonomy');

        $courts = Court::orderBy('tier')->orderBy('sort_order')->get()->groupBy('tier');

        return view('courts.index', compact('courts'));
    }

    /**
     * Show the form for creating a new court/forum.
     */
    public function create(): View
    {
        Gate::authorize('manage-taxonomy');

        return view('courts.create');
    }

    /**
     * Store a newly created court/forum.
     */
    public function store(StoreCourtRequest $request): RedirectResponse
    {
        Gate::authorize('manage-taxonomy');

        Court::create($request->validated());

        return redirect()->route('courts.index')
            ->with('success', 'Court created successfully.');
    }

    /**
     * Show the form for editing the specified court/forum.
     */
    public function edit(Court $court): View
    {
        Gate::authorize('manage-taxonomy');

        return view('courts.edit', compact('court'));
    }

    /**
     * Update the specified court/forum.
     */
    public function update(UpdateCourtRequest $request, Court $court): RedirectResponse
    {
        Gate::authorize('manage-taxonomy');

        $court->update($request->validated());

        return redirect()->route('courts.index')
            ->with('success', 'Court updated successfully.');
    }

    /**
     * Remove the specified court/forum — blocked if still in use by
     * existing cases (deactivate it instead via toggleActive()).
     */
    public function destroy(Court $court): RedirectResponse
    {
        Gate::authorize('manage-taxonomy');

        if ($court->cases()->exists()) {
            return redirect()->route('courts.index')
                ->with('error', 'Cannot delete a court that is in use by existing cases. Deactivate it instead.');
        }

        $court->delete();

        return redirect()->route('courts.index')
            ->with('success', 'Court deleted successfully.');
    }

    /**
     * Toggle a court between active and inactive without deleting it.
     */
    public function toggleActive(Court $court): RedirectResponse
    {
        Gate::authorize('manage-taxonomy');

        $court->update(['is_active' => ! $court->is_active]);

        $label = $court->is_active ? 'activated' : 'deactivated';

        return redirect()->route('courts.index')
            ->with('success', "Court \"{$court->name}\" has been {$label}.");
    }
}
