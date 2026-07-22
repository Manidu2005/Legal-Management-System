<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of all users (partner only).
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-users');

        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        Gate::authorize('manage-users');

        return view('users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        User::create($request->validated());

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        Gate::authorize('manage-users');

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $data = $request->validated();

        // Only update password if provided
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Toggle user status between active and suspended.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $user->update([
            'status' => $user->status === 'active' ? 'suspended' : 'active',
        ]);

        $statusLabel = $user->status === 'active' ? 'activated' : 'suspended';

        return redirect()->route('users.index')
            ->with('success', "User {$user->name} has been {$statusLabel}.");
    }
}
