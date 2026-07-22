<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLegalCaseRequest;
use App\Http\Requests\UpdateLegalCaseRequest;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Http\Request;

class LegalCaseController extends Controller
{
    /**
     * Display a listing of legal cases.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = LegalCase::with(['client', 'assignedAttorney']);

        // Associates only see their assigned cases
        if ($user->role === 'associate') {
            $query->where('assigned_attorney_id', $user->id);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by client name, attorney name, or case type
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('assignedAttorney', function ($attorneyQuery) use ($search) {
                    $attorneyQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('case_type', 'like', "%{$search}%");
            });
        }

        $cases = $query->latest()->paginate(15)->withQueryString();

        return view('cases.index', compact('cases'));
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

        return view('cases.create', compact('clients', 'attorneys'));
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
        $case->load([
            'client',
            'assignedAttorney',
            'courtDates' => fn($q) => $q->orderBy('date', 'desc'),
            'documents' => fn($q) => $q->with('uploadedBy')->latest(),
            'ledgerEntries' => fn($q) => $q->with('recordedBy')->latest(),
        ]);

        $user = $request->user();

        // Associates can only view their own cases
        if ($user->role === 'associate' && $case->assigned_attorney_id !== $user->id) {
            abort(403);
        }

        // Calculate billing summary
        $totalAppearanceFee = 0;
        $ledgerTotal = 0;

        if ($user->can('view-financials')) {
            $courtDateCount = $case->courtDates->count();
            $attorney = $case->assignedAttorney;
            $totalAppearanceFee = $attorney ? $courtDateCount * ($attorney->flat_appearance_rate ?? 0) : 0;
            $ledgerTotal = $case->ledgerEntries->sum('amount');
        }

        return view('cases.show', compact('case', 'totalAppearanceFee', 'ledgerTotal'));
    }

    /**
     * Show the form for editing the specified case.
     */
    public function edit(Request $request, LegalCase $case)
    {
        $user = $request->user();

        // Clerks cannot edit
        if ($user->role === 'clerk') {
            abort(403);
        }

        // Associates can only edit their own cases
        if ($user->role === 'associate' && $case->assigned_attorney_id !== $user->id) {
            abort(403);
        }

        $clients = Client::orderBy('name')->get();
        $attorneys = User::where('role', '!=', 'clerk')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('cases.edit', compact('case', 'clients', 'attorneys'));
    }

    /**
     * Update the specified case.
     */
    public function update(UpdateLegalCaseRequest $request, LegalCase $case)
    {
        $user = $request->user();

        // Clerks cannot update
        if ($user->role === 'clerk') {
            abort(403);
        }

        // Associates can only update their own cases
        if ($user->role === 'associate' && $case->assigned_attorney_id !== $user->id) {
            abort(403);
        }

        $case->update($request->validated());

        return redirect()->route('cases.show', $case)
            ->with('success', 'Case updated successfully.');
    }
}
