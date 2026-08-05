<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\LegalCase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    /**
     * Display a listing of documents with optional filters.
     */
    public function index(Request $request): View
    {
        $query = Document::with(['legalCase.client', 'uploader']);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('case_id')) {
            $query->where('case_id', $request->input('case_id'));
        }

        $documents = $query->latest()->paginate(15)->withQueryString();

        $cases = LegalCase::with('client')
            ->orderBy('id', 'desc')
            ->get();

        return view('documents.index', [
            'documents' => $documents,
            'cases' => $cases,
            'categories' => Document::CATEGORIES,
            'selectedCategory' => $request->input('category'),
            'selectedCaseId' => $request->input('case_id'),
        ]);
    }

    /**
     * Show the form for uploading a new document.
     */
    public function create(): View
    {
        $cases = LegalCase::with('client')
            ->orderBy('id', 'desc')
            ->get();

        return view('documents.create', [
            'cases' => $cases,
            'categories' => Document::CATEGORIES,
        ]);
    }

    /**
     * Store a newly uploaded document.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $file = $request->file('document');
        $path = Storage::disk('public')->putFile('documents', $file);

        Document::create([
            'case_id' => $request->input('case_id'),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'category' => $request->input('category'),
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('documents.index')
            ->with('success', 'Document uploaded successfully.');
    }

    /**
     * Display a single document with details and preview.
     */
    public function show(Document $document): View
    {
        $document->load(['legalCase.client', 'uploader']);

        return view('documents.show', [
            'document' => $document,
        ]);
    }

    /**
     * Download a document file.
     */
    public function download(Document $document): Response|RedirectResponse
    {
        if (Storage::disk('public')->exists($document->file_path)) {
            return Storage::disk('public')->download(
                $document->file_path,
                basename($document->file_path)
            );
        }

        return redirect()
            ->back()
            ->with('error', 'The file could not be found on the server. This may be a demo record without an actual file.');
    }

    /**
     * Delete a document from storage and database.
     */
    public function destroy(Document $document): RedirectResponse
    {
        Storage::disk('public')->delete($document->file_path);

        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'Document deleted successfully.');
    }

    /**
     * Generate a legal PDF document (proxy, affidavit, or deed of transfer).
     */
    public function generatePdf(LegalCase $case, string $type): Response
    {
        $allowedTypes = ['proxy', 'affidavit', 'deed_of_transfer'];

        if (! in_array($type, $allowedTypes)) {
            abort(404, 'Invalid document type.');
        }

        $case->load('client');

        $viewMap = [
            'proxy' => 'pdf.proxy',
            'affidavit' => 'pdf.affidavit',
            'deed_of_transfer' => 'pdf.deed-of-transfer',
        ];

        $titleMap = [
            'proxy' => 'Proxy',
            'affidavit' => 'Affidavit',
            'deed_of_transfer' => 'Deed of Transfer',
        ];

        $pdf = Pdf::loadView($viewMap[$type], [
            'case' => $case,
            'client' => $case->client,
            'generatedDate' => now()->format('d F Y'),
        ]);

        $filename = strtolower(str_replace(' ', '_', $titleMap[$type]))
            . '_case_' . $case->id . '.pdf';

        return $pdf->download($filename);
    }
}
