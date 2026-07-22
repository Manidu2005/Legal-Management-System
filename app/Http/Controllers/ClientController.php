<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    /**
     * Display a listing of all clients with search functionality.
     */
    public function index(Request $request): View
    {
        $query = Client::withCount('cases');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nic', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new client (intake form).
     */
    public function create(): View
    {
        return view('clients.create');
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(StoreClientRequest $request): RedirectResponse
    {
        Client::create($request->validated());

        return redirect()->route('clients.index')
            ->with('success', 'Client registered successfully.');
    }

    /**
     * Display the specified client with their cases.
     */
    public function show(Client $client): View
    {
        $client->load(['cases.assignedAttorney']);

        return view('clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Client $client): View
    {
        return view('clients.edit', compact('client'));
    }

    /**
     * Update the specified client in storage.
     */
    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }
}
