<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only oversight of the rabbit holes: how deep learners get, where they
 * stall, and what each layer costs. Editing a transcript is deliberately not
 * possible — it is the learner's record, and the metrics depend on it.
 */
class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = Conversation::query()
            ->with(['user:id,name,email', 'learningMode:id,name,accent', 'sources:id,conversation_id,site'])
            ->withCount('concepts')
            ->when($request->filled('search'), fn ($query) => $query->where('subject', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('mode'), fn ($query) => $query->where('learning_mode_id', $request->integer('mode')))
            ->when($request->filled('approach'), fn ($query) => $query->where('approach', $request->string('approach')))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.conversations.index', [
            'conversations' => $conversations,
            'stats' => $this->stats(),
        ]);
    }

    public function edit(Conversation $conversation): View
    {
        $conversation->load(['user', 'learningMode', 'folder', 'sources', 'concepts', 'checkpointAttempts']);

        return view('admin.conversations.edit', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->orderBy('id')->get(),
        ]);
    }

    public function destroy(Conversation $conversation): RedirectResponse
    {
        $conversation->delete();

        return redirect()->route('admin.conversation.index')
            ->with('success', __('admin/backend.conversations.deleted'));
    }

    /**
     * The metrics that actually matter: completion of the first layer, depth
     * reached, and retry-to-pass — not messages sent.
     *
     * @return array<string, int|float>
     */
    private function stats(): array
    {
        $total = Conversation::count();
        $pastFirstLayer = Conversation::where('current_depth', '>=', 1)->count();

        $asked = Conversation::where('approach', Conversation::APPROACH_QUESTION)->count();

        return [
            'total' => $total,
            'surfaced' => Conversation::where('status', Conversation::STATUS_SURFACED)->count(),
            'first_layer_rate' => $total > 0 ? round($pastFirstLayer / $total * 100) : 0,
            'average_depth' => round((float) Conversation::avg('current_depth'), 1),
            // How many learners choose to be tested before they are taught. The
            // number that says whether the second path is worth keeping.
            'question_first_rate' => $total > 0 ? round($asked / $total * 100) : 0,
        ];
    }
}
