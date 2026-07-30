<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Learner administration. Deliberately no `create`: learners arrive through the
 * public sign-up (or Google), so the console manages the accounts that exist
 * rather than minting new ones.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->withCount('conversations')
            ->when($request->filled('search'), fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('email', 'like', '%'.$request->string('search').'%')
            ))
            ->when($request->filled('status'), fn ($query) => $query->where('enabled', $request->string('status') === 'enabled'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user): View
    {
        $user->loadCount('conversations');

        return view('admin.users.edit', [
            'user' => $user,
            'holes' => $user->conversations()->latest('updated_at')->limit(10)->get(),
            'streak' => $user->streak,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:60',
            'last_name' => 'nullable|string|max:60',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated + ['enabled' => $request->boolean('enabled')]);

        return redirect()->route('admin.user.index')
            ->with('success', __('admin/backend.users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('admin.user.index')
            ->with('success', __('admin/backend.users.deleted'));
    }
}
