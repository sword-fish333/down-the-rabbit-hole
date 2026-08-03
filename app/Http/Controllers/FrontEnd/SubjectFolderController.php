<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\SubjectFolder;
use App\Services\Chat\SubjectFolderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Organisation: the folder tree a learner files their subjects into.
 *
 * Every action here is a plain form post with a redirect back, so the whole page
 * works without JavaScript — drag-and-drop is an enhancement layered over the
 * same endpoints, not a second implementation of them.
 */
class SubjectFolderController extends Controller
{
    public function __construct(private readonly SubjectFolderService $folders) {}

    public function index(): View
    {
        return view('frontend.subjects.organization', $this->folders->tree(auth()->id()));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'parent_id' => 'nullable|integer',
        ]);

        $result = $this->folders->create($request->user(), $validated['name'], $validated['parent_id'] ?? null);

        return $result->isSuccessfulCheck()
            ? back()->with('success', __('frontend.subjects.folder-created'))
            : back()->with('error', $result->getFirstError());
    }

    /** Rename, reparent, or both — a drop and a rename are the same mutation. */
    public function update(Request $request, SubjectFolder $folder): RedirectResponse
    {
        $this->authorizeOwner($folder);

        $validated = $request->validate([
            'name' => 'nullable|string|max:80',
            'parent_id' => 'nullable|integer',
        ]);

        if ($request->has('parent_id')) {
            $result = $this->folders->move($folder, $validated['parent_id'] ?? null);

            if (! $result->isSuccessfulCheck()) {
                return back()->with('error', $result->getFirstError());
            }
        }

        if (filled($validated['name'] ?? null)) {
            $folder->update(['name' => $validated['name']]);
        }

        return back()->with('success', __('frontend.subjects.folder-saved'));
    }

    /**
     * Deleting a folder deletes the folders inside it and keeps every subject —
     * they fall back to unfiled. Losing a descent to a tidying action would be
     * unforgivable, so the schema makes it impossible (`nullOnDelete`).
     */
    public function destroy(SubjectFolder $folder): RedirectResponse
    {
        $this->authorizeOwner($folder);

        $folder->delete();

        return back()->with('success', __('frontend.subjects.folder-deleted'));
    }

    private function authorizeOwner(SubjectFolder $folder): void
    {
        abort_unless($folder->user_id === auth()->id(), 404);
    }
}
