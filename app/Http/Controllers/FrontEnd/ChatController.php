<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Chat\ChatStreamingService;
use App\Services\Chat\DescentService;
use App\Services\ValidationService;
use App\Traits\ValidationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The descent: start a hole, view it, submit proof / go deeper, and stream the
 * guide's turn. Thin — every rule lives in DescentService / ChatStreamingService.
 */
class ChatController extends Controller
{
    use ValidationHelper;

    public function __construct(
        private readonly DescentService $descent,
        private readonly ChatStreamingService $streaming,
    ) {
        $this->initializeValidator();
    }

    /** Start a new rabbit hole from the home composer. */
    public function descend(): RedirectResponse
    {
        $validated = $this->validateDescend();

        $conversation = $this->descent->start(auth()->user(), $validated['prompt']);
        $this->rememberGuestHole($conversation);

        return redirect()->route('hole.show', $conversation)->with('stream', true);
    }

    public function show(Conversation $conversation): View|RedirectResponse
    {
        if (! $this->canAccess($conversation)) {
            return redirect()->route('home')->with('error', __('frontend.chat.no-access'));
        }

        return view('frontend.chat.show', compact('conversation'));
    }

    /** Submit a checkpoint proof, or ask to go one layer deeper after a pass. */
    public function continue(Conversation $conversation): RedirectResponse
    {
        if (! $this->canAccess($conversation)) {
            return redirect()->route('home')->with('error', __('frontend.chat.no-access'));
        }

        $validated = $this->validateContinue($conversation);
        $this->descent->recordUserProof($conversation, $validated['message'] ?? null);

        return redirect()->route('hole.show', $conversation)->with('stream', true);
    }

    /** SSE endpoint: streams the guide's turn for the conversation's current state. */
    public function stream(Conversation $conversation): StreamedResponse
    {
        $validation = $this->validateStream($conversation);
        if (! $validation->isSuccessfulCheck()) {
            return $this->errorStream($validation->getFirstError());
        }

        $this->descent->recordTurn(auth()->user(), $this->guestKey());

        return $this->streaming->stream($conversation);
    }

    private function validateDescend(): array
    {
        return request()->validate(['prompt' => 'required|string|min:2|max:500']);
    }

    private function validateContinue(Conversation $conversation): array
    {
        $rules = ['message' => 'nullable|string|max:2000'];

        if ($conversation->isCheckpointPending()) {
            $rules['message'] = 'required|string|max:2000';
        }

        return request()->validate($rules);
    }

    private function validateStream(Conversation $conversation): ValidationService
    {
        if (! $this->canAccess($conversation)) {
            return $this->errorEncountered(__('frontend.chat.no-access'));
        }

        if ($conversation->isSurfaced()) {
            return $this->errorEncountered(__('frontend.chat.already-surfaced'));
        }

        if ($this->descent->dailyLimitReached(auth()->user(), $this->guestKey())) {
            return $this->errorEncountered(__('frontend.chat.daily-limit'));
        }

        return $this->successfulCheck();
    }

    /** Owned by the signed-in user, or a guest hole remembered in this session. */
    private function canAccess(Conversation $conversation): bool
    {
        if ($conversation->user_id) {
            return $conversation->user_id === auth()->id();
        }

        return in_array($conversation->id, session('dth_holes', []), true);
    }

    private function rememberGuestHole(Conversation $conversation): void
    {
        if ($conversation->user_id) {
            return;
        }

        $holes = session('dth_holes', []);
        $holes[] = $conversation->id;
        session(['dth_holes' => $holes]);
    }

    private function guestKey(): string
    {
        return session()->getId();
    }

    private function errorStream(string $message): StreamedResponse
    {
        return response()->stream(function () use ($message) {
            echo 'event: error'."\n";
            echo 'data: '.json_encode(['message' => $message])."\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
