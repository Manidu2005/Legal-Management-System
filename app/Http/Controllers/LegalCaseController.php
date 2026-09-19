<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresCaseAccessCode;
use App\Http\Requests\StoreLegalCaseRequest;
use App\Http\Requests\UpdateLegalCaseRequest;
use App\Models\CaseCategory;
use App\Models\Client;
use App\Models\Court;
use App\Models\LegalCase;
use App\Models\ResearchNote;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class LegalCaseController extends Controller
{
    use EnsuresCaseAccessCode;

    /**
     * Display a listing of legal cases.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = LegalCase::with(['client', 'assignedAttorney', 'caseCategory.parent.parent', 'court']);

        // Associates only see their assigned cases
        if ($user->role === 'associate') {
            $query->where('assigned_attorney_id', $user->id);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by main case-type category (level 1) if provided — resolve to
        // the set of leaf (level 3) category ids under that main type first.
        if ($request->filled('main_category_id')) {
            $leafIds = CaseCategory::query()
                ->where('level', CaseCategory::LEVEL_SPECIFIC_TYPE)
                ->whereHas('parent', function ($q) use ($request) {
                    $q->where('parent_id', $request->input('main_category_id'));
                })
                ->pluck('id');
            $query->whereIn('case_category_id', $leafIds);
        }

        // Filter by court if provided
        if ($request->filled('court_id')) {
            $query->where('court_id', $request->input('court_id'));
        }

        // Search by client name, attorney name, case category, or case name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('assignedAttorney', function ($attorneyQuery) use ($search) {
                    $attorneyQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('caseCategory', function ($categoryQuery) use ($search) {
                    $categoryQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('case_type_other', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $cases = $query->latest()->paginate(15)->withQueryString();

        $mainCategories = CaseCategory::active()->level(CaseCategory::LEVEL_MAIN_TYPE)->orderBy('sort_order')->get();
        $courts = Court::active()->orderBy('sort_order')->get();

        return view('cases.index', compact('cases', 'mainCategories', 'courts'));
    }

    /**
     * Show the form for creating a new case.
     */
    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $attorneys = User::where('role', '!=', 'clerk')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $caseCategoryTree = $this->caseCategoryTree();
        $courts = Court::active()->orderBy('sort_order')->get();

        return view('cases.create', compact('clients', 'attorneys', 'caseCategoryTree', 'courts'));
    }

    /**
     * Store a newly created case.
     */
    public function store(StoreLegalCaseRequest $request)
    {
        LegalCase::create($request->validated());

        return redirect()->route('cases.index')
            ->with('success', 'Case created successfully.');
    }

    /**
     * Display the specified case.
     */
    public function show(Request $request, LegalCase $case)
    {
        if ($redirect = $this->redirectForCaseAccessCode($case)) {
            return $redirect;
        }

        $this->authorize('view', $case);

        $case->load([
            'client',
            'assignedAttorney',
            'caseCategory.parent.parent',
            'court',
            'courtDates' => fn($q) => $q->orderBy('date', 'desc'),
            'documents' => fn($q) => $q->with('uploadedBy')->latest(),
            'ledgerEntries' => fn($q) => $q->with('recordedBy')->latest(),
            'researchNotes' => fn($q) => $q->with('addedBy')->latest(),
            'caseJudgments' => fn($q) => $q->with(['judgment', 'addedBy'])->latest(),
        ]);

        $user = $request->user();

        // Calculate billing summary
        $totalAppearanceFee = 0;
        $ledgerTotal = 0;

        if ($user->can('view-financials')) {
            $totalAppearanceFee = $case->assignedAttorney ? $case->totalAppearanceFee() : 0;
            $ledgerTotal = $case->ledgerEntries->sum('amount');
        }

        $researchCategories = ResearchNote::CATEGORIES;

        return view('cases.show', compact('case', 'totalAppearanceFee', 'ledgerTotal', 'researchCategories'));
    }

    /**
     * Show the form for editing the specified case.
     */
    public function edit(Request $request, LegalCase $case)
    {
        // Clerks never edit — even a code-verified clerk only ever gains
        // view rights via CaseAccessPolicy::view(), never edit rights.
        abort_if($request->user()->role === 'clerk', 403);

        $this->authorize('view', $case);

        $clients = Client::orderBy('name')->get();
        $attorneys = User::where('role', '!=', 'clerk')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $caseCategoryTree = $this->caseCategoryTree();
        $courts = Court::active()->orderBy('sort_order')->get();

        return view('cases.edit', compact('case', 'clients', 'attorneys', 'caseCategoryTree', 'courts'));
    }

    /**
     * Update the specified case.
     */
    public function update(UpdateLegalCaseRequest $request, LegalCase $case)
    {
        // Clerks never update — even a code-verified clerk only ever gains
        // view rights via CaseAccessPolicy::view(), never update rights.
        abort_if($request->user()->role === 'clerk', 403);

        $this->authorize('view', $case);

        $case->update($request->validated());

        return redirect()->route('cases.show', $case)
            ->with('success', 'Case updated successfully.');
    }

    /**
     * Generate and download a PDF case brief.
     */
    public function exportBrief(Request $request, LegalCase $case): Response
    {
        $user = $request->user();

        // Associates can only export their own cases
        if ($user->role === 'associate' && $case->assigned_attorney_id !== $user->id) {
            abort(403);
        }

        $case->load([
            'client',
            'assignedAttorney',
            'caseCategory',
            'court',
            'courtDates' => fn ($q) => $q->orderBy('date', 'asc'),
            'documents',
            'ledgerEntries',
        ]);

        $caseRef = 'LEX-' . $case->created_at->format('Y') . '-' . str_pad($case->id, 3, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('pdf.case-brief', [
            'case'      => $case,
            'caseRef'   => $caseRef,
            'generatedAt' => now()->format('d F Y, g:i A'),
        ])->setPaper('a4', 'portrait');

        $filename = 'case-brief-' . strtolower($caseRef) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Remove the specified case. Partner-only — blocked with a friendly
     * message if the case has related records that would otherwise fail
     * on their restrict-delete foreign keys (documents, ledger entries,
     * court dates).
     */
    public function destroy(LegalCase $case): RedirectResponse
    {
        Gate::authorize('manage-users');

        $blockers = [];
        if ($case->documents()->exists()) {
            $blockers[] = 'documents';
        }
        if ($case->ledgerEntries()->exists()) {
            $blockers[] = 'ledger entries';
        }
        if ($case->courtDates()->exists()) {
            $blockers[] = 'court dates';
        }

        if (! empty($blockers)) {
            return redirect()->route('cases.index')
                ->with('error', 'Cannot delete this case — it still has ' . implode(', ', $blockers) . '. Remove those first.');
        }

        $case->delete();

        return redirect()->route('cases.index')
            ->with('success', 'Case deleted successfully.');
    }

    /**
     * Build the active case-category taxonomy as a nested tree (Main Type ->
     * Group -> Specific type), ready to be JSON-encoded for the cascading
     * Alpine.js selects on the case create/edit forms.
     */
    private function caseCategoryTree()
    {
        return CaseCategory::active()
            ->roots()
            ->level(CaseCategory::LEVEL_MAIN_TYPE)
            ->orderBy('sort_order')
            ->with(['children' => function ($query) {
                $query->active()->orderBy('sort_order');
            }, 'children.children' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->get();
    }
}
