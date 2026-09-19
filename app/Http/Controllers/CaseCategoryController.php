<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCaseCategoryRequest;
use App\Http\Requests\UpdateCaseCategoryRequest;
use App\Models\CaseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CaseCategoryController extends Controller
{
    /**
     * Display the full case-category taxonomy, grouped by level for an
     * easy-to-scan tree view (partner only).
     */
    public function index(): View
    {
        Gate::authorize('manage-taxonomy');

        $mainTypes = CaseCategory::level(CaseCategory::LEVEL_MAIN_TYPE)
            ->with(['children' => function ($query) {
                $query->orderBy('sort_order');
            }, 'children.children' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('case-categories.index', compact('mainTypes'));
    }

    /**
     * Show the form for creating a new category at any level.
     */
    public function create(Request $request): View
    {
        Gate::authorize('manage-taxonomy');

        $mainTypes = CaseCategory::level(CaseCategory::LEVEL_MAIN_TYPE)->orderBy('sort_order')->get();
        $groups = CaseCategory::level(CaseCategory::LEVEL_GROUP)->with('parent')->orderBy('sort_order')->get();
        $preselectedParentId = $request->integer('parent_id') ?: null;
        $preselectedLevel = $request->integer('level') ?: null;

        return view('case-categories.create', compact('mainTypes', 'groups', 'preselectedParentId', 'preselectedLevel'));
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCaseCategoryRequest $request): RedirectResponse
    {
        Gate::authorize('manage-taxonomy');

        CaseCategory::create($request->validated());

        return redirect()->route('case-categories.index')
            ->with('success', 'Case category created successfully.');
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(CaseCategory $caseCategory): View
    {
        Gate::authorize('manage-taxonomy');

        $mainTypes = CaseCategory::level(CaseCategory::LEVEL_MAIN_TYPE)->orderBy('sort_order')->get();
        $groups = CaseCategory::level(CaseCategory::LEVEL_GROUP)->with('parent')->orderBy('sort_order')->get();

        return view('case-categories.edit', [
            'caseCategory' => $caseCategory,
            'mainTypes' => $mainTypes,
            'groups' => $groups,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCaseCategoryRequest $request, CaseCategory $caseCategory): RedirectResponse
    {
        Gate::authorize('manage-taxonomy');

        $caseCategory->update($request->validated());

        return redirect()->route('case-categories.index')
            ->with('success', 'Case category updated successfully.');
    }

    /**
     * Remove the specified category — blocked if it still has children or
     * cases attached (deactivate it instead via toggleActive()).
     */
    public function destroy(CaseCategory $caseCategory): RedirectResponse
    {
        Gate::authorize('manage-taxonomy');

        if ($caseCategory->children()->exists()) {
            return redirect()->route('case-categories.index')
                ->with('error', 'Cannot delete a category that still has sub-categories. Delete or move those first.');
        }

        if ($caseCategory->cases()->exists()) {
            return redirect()->route('case-categories.index')
                ->with('error', 'Cannot delete a category that is in use by existing cases. Deactivate it instead.');
        }

        $caseCategory->delete();

        return redirect()->route('case-categories.index')
            ->with('success', 'Case category deleted successfully.');
    }

    /**
     * Toggle a category between active and inactive without deleting it.
     */
    public function toggleActive(CaseCategory $caseCategory): RedirectResponse
    {
        Gate::authorize('manage-taxonomy');

        $caseCategory->update(['is_active' => ! $caseCategory->is_active]);

        $label = $caseCategory->is_active ? 'activated' : 'deactivated';

        return redirect()->route('case-categories.index')
            ->with('success', "Category \"{$caseCategory->name}\" has been {$label}.");
    }
}
