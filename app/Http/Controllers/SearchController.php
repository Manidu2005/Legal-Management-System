<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
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

        if ($query !== '') {
            $term = '%' . $query . '%';

            $clients = Client::where('name', 'LIKE', $term)
                ->orWhere('nic', 'LIKE', $term)
                ->limit(50)
                ->get();

            $cases = LegalCase::with('client')
                ->where('id', 'LIKE', $term)
                ->orWhere('case_type', 'LIKE', $term)
                ->limit(50)
                ->get();

            $documents = Document::with(['legalCase.client', 'uploader'])
                ->where('file_path', 'LIKE', $term)
                ->limit(50)
                ->get();
        }

        return view('search.index', [
            'query' => $query,
            'clients' => $clients,
            'cases' => $cases,
            'documents' => $documents,
        ]);
    }
}
