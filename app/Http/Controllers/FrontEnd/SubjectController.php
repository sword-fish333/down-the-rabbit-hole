<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Chat\MasteryService;
use App\Services\Chat\SubjectFolderService;
use App\Services\Chat\SubjectLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The learner's library: every subject they've opened, what they proved in it,
 * and the two things they can do to one from outside a descent — file it, or
 * publish it read-only.
 *
 * `index` serves both the page and its own lazy-load: asked for a fragment it
 * returns only the next page of rows. One route, one query builder, and the row
 * markup exists exactly once — in Blade, not duplicated in JavaScript.
 */
class SubjectController extends Controller
{
    public function __construct(
        private readonly SubjectLibraryService $library,
        private readonly SubjectFolderService $folders,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:120',
            'filter' => ['nullable', Rule::in(Conversation::FILTERS)],
            'cursor' => 'nullable|string|max:255',
            'fragment' => 'nullable|boolean',
        ]);

        $subjects = $this->library->page(
            auth()->id(),
            $validated['q'] ?? null,
            $validated['filter'] ?? null,
            $validated['cursor'] ?? null,
        );

        $data = [
            'subjects' => $subjects,
            'search' => $validated['q'] ?? '',
            'filter' => $validated['filter'] ?? Conversation::FILTER_ALL,
        ];

        // The infinite-scroll and live-search paths both want rows, not chrome.
        return $request->boolean('fragment')
            ? view('frontend.subjects.partials.rows', $data)
            : view('frontend.subjects.index', $data);
    }

    /** Remove a selection. Ownership is enforced inside the delete itself. */
    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $deleted = $this->library->delete($request->user(), $validated['ids']);

        return back()->with('success', trans_choice('frontend.subjects.deleted', $deleted, ['count' => $deleted]));
    }

    /** Publish or unpublish a subject as a read-only page. */
    public function share(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeOwner($conversation);

        if ($conversation->isShared()) {
            $conversation->unshare();

            return back()->with('success', __('frontend.subjects.unshared'));
        }

        $conversation->share();

        return back()->with('success', __('frontend.subjects.shared-notice', [
            'url' => route('subject.shared', $conversation->share_token),
        ]));
    }

    /** Drag-and-drop target: file this subject into a folder, or out of one. */
    public function file(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeOwner($conversation);

        $validated = $request->validate(['folder_id' => 'nullable|integer']);

        $result = $this->folders->fileSubject($conversation, $validated['folder_id'] ?? null);

        return $result->isSuccessfulCheck()
            ? back()->with('success', __('frontend.subjects.filed'))
            : back()->with('error', $result->getFirstError());
    }

    /**
     * A shared subject, read by anyone holding the link. The transcript only —
     * no composer, no checkpoint, and nothing that could advance someone else's
     * descent.
     */
    public function shared(string $token, MasteryService $mastery): View
    {
        $conversation = Conversation::query()
            ->where('share_token', $token)
            ->with(['user:id,first_name,last_name,name', 'learningMode', 'sources'])
            ->firstOrFail();

        return view('frontend.subjects.shared', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->orderBy('id')->get(),
            'concepts' => $conversation->concepts()->orderBy('first_seen_depth')->orderBy('name')->get(),
            'mastery' => $mastery->tally($conversation),
        ]);
    }

    /** 404 rather than 403: whether another learner's subject exists is not public. */
    private function authorizeOwner(Conversation $conversation): void
    {
        abort_unless($conversation->user_id === auth()->id(), 404);
    }
}
