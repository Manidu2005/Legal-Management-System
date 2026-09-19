<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use App\Models\Judgment;
use App\Models\LegalCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Display search results grouped by type.
     */
    public function index(Request $request): View
    {
        $query = $request->input('q', '');

        $clients = collect();
        $cases = collect();
        $documents = collect();
        $judgments = collect();

        if ($query !== '') {
            $term = '%' . $query . '%';

            $clients = Client::where('name', 'LIKE', $term)
                ->orWhere('nic', 'LIKE', $term)
                ->limit(50)
                ->get();

            $cases = LegalCase::with(['client', 'caseCategory'])
                ->where('id', 'LIKE', $term)
                ->orWhere('case_type_other', 'LIKE', $term)
                ->orWhereHas('caseCategory', function ($categoryQuery) use ($term) {
                    $categoryQuery->where('name', 'LIKE', $term);
                })
                ->limit(50)
                ->get();

            $documents = Document::with(['legalCase.client', 'uploader'])
                ->where(function ($documentQuery) use ($term) {
                    $documentQuery->where('name', 'LIKE', $term)
                        ->orWhere('file_path', 'LIKE', $term);
                })
                ->limit(50)
                ->get();

            $judgments = Judgment::query()
                ->where('title', 'LIKE', $term)
                ->orWhere('summary', 'LIKE', $term)
                ->orWhere('court', 'LIKE', $term)
                ->orWhere('cited_acts', 'LIKE', $term)
                ->limit(50)
                ->get();
        }

        return view('search.index', [
            'query' => $query,
            'clients' => $clients,
            'cases' => $cases,
            'documents' => $documents,
            'judgments' => $judgments,
        ]);
    }
}
